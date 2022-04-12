<?php

namespace SilverStripe\ExternalLinks\Tasks;

use DOMNode;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Debug;
use SilverStripe\ExternalLinks\Model\BrokenExternalLink;
use SilverStripe\ExternalLinks\Model\BrokenExternalPageTrack;
use SilverStripe\ExternalLinks\Model\BrokenExternalPageTrackStatus;
use SilverStripe\ExternalLinks\Tasks\LinkChecker;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;

class CheckExternalLinksTask extends BuildTask
{
    private static $dependencies = [
        'LinkChecker' => '%$' . LinkChecker::class
    ];

    private static $segment = 'CheckExternalLinksTask';

    /**
     * Define a list of HTTP response codes that should not be treated as "broken", where they usually
     * might be.
     *
     * @config
     * @var array
     */
    private static $ignore_codes = [];

    /**
     * @var bool
     */
    protected $silent = false;

    /**
     * @var LinkChecker
     */
    protected $linkChecker;

    protected $title = 'Checking broken External links in the SiteTree';

    protected $description = 'A task that records external broken links in the SiteTree';

    protected $enabled = true;

    /**
     * Log a message
     *
     * @param string $message
     */
    protected function log($message)
    {
        if (!$this->silent) {
            Debug::message($message);
        }
    }

    public function run($request)
    {
        $this->runLinksCheck();
    }
    /**
     * Turn on or off message output
     *
     * @param bool $silent
     */
    public function setSilent($silent)
    {
        $this->silent = $silent;
    }

    /**
     * @param LinkChecker $linkChecker
     */
    public function setLinkChecker(LinkChecker $linkChecker)
    {
        $this->linkChecker = $linkChecker;
    }

    /**
     * @return LinkChecker
     */
    public function getLinkChecker()
    {
        return $this->linkChecker;
    }

    /**
     * Check the status of a single link on a page
     *
     * @param BrokenExternalPageTrack $pageTrack
     * @param DOMNode $link
     */
    protected function checkPageLink(BrokenExternalPageTrack $pageTrack, DOMNode $link)
    {
        $class = $link->getAttribute('class');
        $href = $link->getAttribute('href');
        $markedBroken = preg_match('/\b(ss-broken)\b/', $class ?? '');

        // Check link
        $httpCode = $this->linkChecker->checkLink($href);
        if ($httpCode === null) {
            return; // Null link means uncheckable, such as an internal link
        }

        // If this code is broken then mark as such
        if ($foundBroken = $this->isCodeBroken($httpCode)) {
            // Create broken record
            $brokenLink = new BrokenExternalLink();
            $brokenLink->Link = $href;
            $brokenLink->HTTPCode = $httpCode;
            $brokenLink->TrackID = $pageTrack->ID;
            $brokenLink->StatusID = $pageTrack->StatusID; // Slight denormalisation here for performance reasons
            $brokenLink->write();
        }

        // Check if we need to update CSS class, otherwise return
        if ($markedBroken == $foundBroken) {
            return;
        }
        if ($foundBroken) {
            $class .= ' ss-broken';
        } else {
            $class = preg_replace('/\s*\b(ss-broken)\b\s*/', ' ', $class ?? '');
        }
        $link->setAttribute('class', trim($class ?? ''));
    }

    /**
     * Determine if the given HTTP code is "broken"
     *
     * @param int $httpCode
     * @return bool True if this is a broken code
     */
    protected function isCodeBroken($httpCode)
    {
        // Null represents no request attempted
        if ($httpCode === null) {
            return false;
        }

        // do we have any whitelisted codes
        $ignoreCodes = $this->config()->get('ignore_codes');
        if (is_array($ignoreCodes) && in_array($httpCode, $ignoreCodes ?? [])) {
            return false;
        }

        // Check if code is outside valid range
        return $httpCode < 200 || $httpCode > 302;
    }

    /**
     * Runs the links checker and returns the track used
     *
     * @param int $limit Limit to number of pages to run, or null to run all
     * @return BrokenExternalPageTrackStatus
     */
    public function runLinksCheck($limit = null)
    {
        // Check the current status
        $status = BrokenExternalPageTrackStatus::get_or_create();

        // Calculate pages to run
        $pageTracks = $status->getIncompleteTracks();
        if ($limit) {
            $pageTracks = $pageTracks->limit($limit);
        }

        // Check each page
        foreach ($pageTracks as $pageTrack) {
            // Flag as complete
            $pageTrack->Processed = 1;
            $pageTrack->write();

            // Check value of html area
            $page = $pageTrack->Page();
            $this->log("Checking {$page->Title}");
            $htmlValue = Injector::inst()->create('HTMLValue', $page->Content);
            if (!$htmlValue->isValid()) {
                continue;
            }

            // Check each link
            $links = $htmlValue->getElementsByTagName('a');
            foreach ($links as $link) {
                $this->checkPageLink($pageTrack, $link);
            }

            // Update content of page based on link fixes / breakages
            $htmlValue->saveHTML();
            $page->Content = $htmlValue->getContent();
            try {
                $page->write();
            } catch (ValidationException $ex) {
                $this->log("Exception caught for {$page->Title}, skipping. Message: " . $ex->getMessage());
                continue;
            }

            // Once all links have been created for this page update HasBrokenLinks
            $count = $pageTrack->BrokenLinks()->count();
            $this->log("Found {$count} broken links");
            if ($count) {
                $siteTreeTable = DataObject::getSchema()->tableName(SiteTree::class);
                // Bypass the ORM as syncLinkTracking does not allow you to update HasBrokenLink to true
                DB::query(sprintf(
                    'UPDATE "%s" SET "HasBrokenLink" = 1 WHERE "ID" = \'%d\'',
                    $siteTreeTable,
                    intval($pageTrack->ID)
                ));
            }
        }

        $status->updateJobInfo('Updating completed pages');
        $status->updateStatus();
        return $status;
    }

    private function updateCompletedPages($trackID = 0)
    {
        $noPages = BrokenExternalPageTrack::get()
            ->filter(array(
                'TrackID' => $trackID,
                'Processed' => 1
            ))
            ->count();
        $track = BrokenExternalPageTrackStatus::get_latest();
        $track->CompletedPages = $noPages;
        $track->write();
        return $noPages;
    }

    private function updateJobInfo($message)
    {
        $track = BrokenExternalPageTrackStatus::get_latest();
        if ($track) {
            $track->JobInfo = $message;
            $track->write();
        }
    }
}
