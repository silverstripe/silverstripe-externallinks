<?php

namespace SilverStripe\ExternalLinks\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ExternalLinks\Model\BrokenExternalPageTrackStatus;
use SilverStripe\ExternalLinks\Model\BrokenExternalLink;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObject;

/**
 * Represents a track for a single page
 */
class BrokenExternalPageTrack extends DataObject
{

    private static $db = array(
        'Processed' => 'Boolean'
    );

    private static $has_one = array(
        'Page' => SiteTree::class,
        'Status' => BrokenExternalPageTrackStatus::class
    );

    private static $has_many = array(
        'BrokenLinks' => BrokenExternalLink::class
    );

    /**
     * @return SiteTree
     */
    public function Page()
    {
        return Versioned::get_by_stage(SiteTree::class, 'Stage')
            ->byID($this->PageID);
    }
}
