<?php

namespace SilverStripe\ExternalLinks\Tests;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ExternalLinks\Model\BrokenExternalPageTrackStatus;
use SilverStripe\ExternalLinks\Reports\BrokenExternalLinksReport;
use SilverStripe\ExternalLinks\Tasks\CheckExternalLinksTask;
use SilverStripe\ExternalLinks\Tasks\LinkChecker;
use SilverStripe\ExternalLinks\Tests\Stubs\ExternalLinksTestPage;
use SilverStripe\ExternalLinks\Tests\Stubs\PretendLinkChecker;
use SilverStripe\i18n\i18n;
use SilverStripe\Reports\Report;

class ExternalLinksTest extends SapphireTest
{

    protected static $fixture_file = 'ExternalLinksTest.yml';

    protected static $extra_dataobjects = array(
        ExternalLinksTestPage::class
    );

    protected function setUp(): void
    {
        parent::setUp();

        // Stub link checker
        $checker = new PretendLinkChecker;
        Injector::inst()->registerService($checker, LinkChecker::class);
    }

    public function testLinks()
    {
        // Run link checker
        $task = CheckExternalLinksTask::create();
        $task->setSilent(true); // Be quiet during the test!
        $task->runLinksCheck();

        // Get all links checked
        $status = BrokenExternalPageTrackStatus::get_latest();
        $this->assertEquals('Completed', $status->Status);
        $this->assertEquals(5, $status->TotalPages);
        $this->assertEquals(5, $status->CompletedPages);

        // Check all pages have had the correct HTML adjusted
        for ($i = 1; $i <= 5; $i++) {
            $page = $this->objFromFixture(ExternalLinksTestPage::class, 'page'.$i);
            $this->assertNotEmpty($page->Content);
            $this->assertEquals(
                $page->ExpectedContent,
                $page->Content,
                "Assert that the content of page{$i} has been updated"
            );
        }

        // Check that the correct report of broken links is generated
        $links = $status
            ->BrokenLinks()
            ->sort('Link');

        $this->assertEquals(4, $links->count());
        $this->assertEquals(
            array(
                'http://www.broken.com',
                'http://www.broken.com/url/thing',
                'http://www.broken.com/url/thing',
                'http://www.nodomain.com'
            ),
            array_values($links->map('ID', 'Link')->toArray() ?? [])
        );

        // Check response codes are correct
        $expected = array(
            'http://www.broken.com' => 403,
            'http://www.broken.com/url/thing' => 404,
            'http://www.nodomain.com' => 0
        );
        $actual = $links->map('Link', 'HTTPCode')->toArray();
        $this->assertEquals($expected, $actual);

        // Check response descriptions are correct
        i18n::set_locale('en_NZ');
        $expected = array(
            'http://www.broken.com' => '403 (Forbidden)',
            'http://www.broken.com/url/thing' => '404 (Not Found)',
            'http://www.nodomain.com' => '0 (Server Not Available)'
        );
        $actual = $links->map('Link', 'HTTPCodeDescription')->toArray();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that broken links appears in the reports list
     */
    public function testReportExists()
    {
        $reports = Report::get_reports();
        $reportNames = array();
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertContains(
            BrokenExternalLinksReport::class,
            $reportNames,
            'BrokenExternalLinksReport is in reports list'
        );
    }

    public function testArchivedPagesAreHiddenFromReport()
    {
        // Run link checker
        $task = CheckExternalLinksTask::create();
        $task->setSilent(true); // Be quiet during the test!
        $task->runLinksCheck();

        // Ensure report lists all broken links
        $this->assertEquals(4, BrokenExternalLinksReport::create()->sourceRecords()->count());

        // Archive a page
        $page = $this->objFromFixture(ExternalLinksTestPage::class, 'page1');
        $page->doArchive();

        // Ensure report does not list the link associated with an archived page
        $this->assertEquals(3, BrokenExternalLinksReport::create()->sourceRecords()->count());
    }
}
