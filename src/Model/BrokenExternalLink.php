<?php

namespace SilverStripe\ExternalLinks\Model;

use SilverStripe\ExternalLinks\Model\BrokenExternalPageTrack;
use SilverStripe\ExternalLinks\Model\BrokenExternalPageTrackStatus;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataObject;

/**
 * Represents a single link checked for a single run that is broken
 *
 * @method BrokenExternalPageTrack Track()
 * @method BrokenExternalPageTrackStatus Status()
 */
class BrokenExternalLink extends DataObject
{
    private static $table_name = 'BrokenExternalLink';

    private static $db = array(
        'Link' => 'Varchar(2083)', // 2083 is the maximum length of a URL in Internet Explorer.
        'HTTPCode' =>'Int'
    );

    private static $has_one = array(
        'Track' => BrokenExternalPageTrack::class,
        'Status' => BrokenExternalPageTrackStatus::class
    );

    private static $summary_fields = array(
        'Created' => 'Checked',
        'Link' => 'External Link',
        'HTTPCodeDescription' => 'HTTP Error Code',
        'Page.Title' => 'Page link is on'
    );

    private static $searchable_fields = array(
        'HTTPCode' => array('title' => 'HTTP Code')
    );

    /**
     * @return SiteTree
     */
    public function Page()
    {
        return $this->Track()->Page();
    }

    public function canEdit($member = false)
    {
        return false;
    }

    public function canView($member = false)
    {
        $member = $member ? $member : Member::currentUser();
        $codes = array('content-authors', 'administrators');
        return Permission::checkMember($member, $codes);
    }

    /**
     * Retrieve a human readable description of a response code
     *
     * @return string
     */
    public function getHTTPCodeDescription()
    {
        $code = $this->HTTPCode;

        try {
            $response = HTTPResponse::create('', $code);
            // Assume that $code = 0 means there was no response
            $description = $code ?
                $response->getStatusDescription() :
                _t(__CLASS__ . '.NOTAVAILABLE', 'Server Not Available');
        } catch (InvalidArgumentException $e) {
            $description = _t(__CLASS__ . '.UNKNOWNRESPONSE', 'Unknown Response Code');
        }

        return sprintf("%d (%s)", $code, $description);
    }
}
