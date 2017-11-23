<?php

namespace SilverStripe\ExternalLinks\Tests;

use SilverStripe\ExternalLinks\Tasks\LinkChecker;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ExternalLinks\Tasks\CheckExternalLinksTask;
use SilverStripe\ExternalLinks\Model\BrokenExternalPageTrackStatus;
use SilverStripe\i18n\i18n;
use SilverStripe\Reports\Report;
use SilverStripe\ExternalLinks\Reports\BrokenExternalLinksReport;
use SilverStripe\Dev\SapphireTest;

class ExternalLinksTest extends SapphireTest
{

    protected static $fixture_file = 'ExternalLinksTest.yml';

    protected $extraDataObjects = array(
        'ExternalLinksTestPage'
    );

    protected $illegalExtensions = array(
        'SiteTree' => array('Translatable')
    );

    public function setUpOnce()
    {
        if (class_exists('Phockito')) {
            Phockito::include_hamcrest(false);
        }

        parent::setUpOnce();
    }

    public function setUp()
    {
        parent::setUp();

        // Check dependencies
        if (!class_exists('Phockito')) {
            $this->skipTest = true;
            return $this->markTestSkipped("These tests need the Phockito module installed to run");
        }

        // Mock link checker
        $checker = Phockito::mock(LinkChecker::class);
        Phockito::when($checker)
            ->checkLink('http://www.working.com')
            ->return(200);

        Phockito::when($checker)
            ->checkLink('http://www.broken.com/url/thing') // 404 on working site
            ->return(404);

        Phockito::when($checker)
            ->checkLink('http://www.broken.com') // 403 on working site
            ->return(403);

        Phockito::when($checker)
            ->checkLink('http://www.nodomain.com') // no ping
            ->return(0);

        Phockito::when($checker)
            ->checkLink('/internal/link')
            ->return(null);

        Phockito::when($checker)
            ->checkLink('[sitetree_link,id=9999]')
            ->return(null);

        Phockito::when($checker)
            ->checkLink('home')
            ->return(null);

        Phockito::when($checker)
            ->checkLink('broken-internal')
            ->return(null);

        Phockito::when($checker)
            ->checkLink('[sitetree_link,id=1]')
            ->return(null);

        Phockito::when($checker)
            ->checkLink(Hamcrest_Matchers::anything()) // anything else is 404
            ->return(404);

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
            $page = $this->objFromFixture('ExternalLinksTestPage', 'page'.$i);
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
            array_values($links->map('ID', 'Link')->toArray())
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
            $reportNames[] = $report->class;
        }
        $this->assertContains(
            BrokenExternalLinksReport::class,
            $reportNames,
            'BrokenExternalLinksReport is in reports list'
        );
    }
}
