<?php

namespace SilverStripe\ExternalLinks\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\i18n\i18nEntityProvider;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;

/**
 * Represents the status of a track run
 *
 * @property int $TotalPages Get total pages count
 * @property int $CompletedPages Get completed pages count
 * @method HasManyList<BrokenExternalLink> BrokenLinks()
 * @method HasManyList<BrokenExternalPageTrack> TrackedPages()
 */
class BrokenExternalPageTrackStatus extends DataObject implements i18nEntityProvider
{
    private static $table_name = 'BrokenExternalPageTrackStatus';

    private static $db = array(
        'Status' => 'Enum("Completed, Running", "Running")',
        'JobInfo' => 'Varchar(255)'
    );

    private static $has_many = array(
        'TrackedPages' => BrokenExternalPageTrack::class,
        'BrokenLinks' => BrokenExternalLink::class
    );

    /**
     * Get the latest track status
     *
     * @return BrokenExternalPageTrackStatus
     */
    public static function get_latest()
    {
        return self::get()
            ->sort('ID', 'DESC')
            ->first();
    }

    /**
     * Returns the list of provided translations for this object
     *
     * @return array
     */
    public function provideI18nEntities()
    {
        return [
            __CLASS__ . '.SINGULARNAME' => 'Broken External Page Track Status',
            __CLASS__ . '.PLURALNAME' => 'Broken External Page Track Statuses',
            __CLASS__ . '.PLURALS' => [
              'one' => 'A Broken External Page Track Status',
              'other' => '{count} Broken External Page Track Statuses',
            ],
        ];
    }

    /**
     * Gets the list of Pages yet to be checked
     *
     * @return DataList<SiteTree>|void
     */
    public function getIncompletePageList()
    {
        $pageIDs = $this
            ->getIncompleteTracks()
            ->column('PageID');
        if ($pageIDs) {
            return Versioned::get_by_stage(SiteTree::class, 'Stage')
            ->byIDs($pageIDs);
        }
    }

    /**
     * Get the list of incomplete BrokenExternalPageTrack
     *
     * @return DataList<BrokenExternalPageTrack>
     */
    public function getIncompleteTracks()
    {
        return $this
            ->TrackedPages()
            ->filter('Processed', 0);
    }

    /**
     * Get total pages count
     *
     * @return int
     */
    public function getTotalPages()
    {
        return $this->TrackedPages()->count();
    }

    /**
     * Get completed pages count
     *
     * @return int
     */
    public function getCompletedPages()
    {
        return $this
            ->TrackedPages()
            ->filter('Processed', 1)
            ->count();
    }

    /**
     * Returns the latest run, or otherwise creates a new one
     *
     * @return BrokenExternalPageTrackStatus
     */
    public static function get_or_create()
    {
        // Check the current status
        $status = self::get_latest();
        if ($status && $status->Status == 'Running') {
            $status->updateStatus();
            return $status;
        }

        return self::create_status();
    }

    /**
     * Create and prepare a new status
     *
     * @return BrokenExternalPageTrackStatus
     */
    public static function create_status()
    {
        // If the script is to be started create a new status
        $status = self::create();
        $status->updateJobInfo('Creating new tracking object');

        // Setup all pages to test
        $pageIDs = Versioned::get_by_stage(SiteTree::class, 'Stage')
            ->column('ID');
        foreach ($pageIDs as $pageID) {
            $trackPage = BrokenExternalPageTrack::create();
            $trackPage->PageID = $pageID;
            $trackPage->StatusID = $status->ID;
            $trackPage->write();
        }

        return $status;
    }

    public function updateJobInfo($message)
    {
        $this->JobInfo = $message;
        $this->write();
    }

    /**
     * Self check status
     */
    public function updateStatus()
    {
        if ($this->CompletedPages == $this->TotalPages) {
            $this->Status = 'Completed';
            $this->updateJobInfo('Setting to completed');
        }
    }
}
