<?php

namespace SilverStripe\ExternalLinks\Model;

use InvalidArgumentException;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Represents a single link checked for a single run that is broken
 *
 * @property string Link
 * @property int HTTPCode
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
        $member = $member ? $member : Security::getCurrentUser();
        $codes = ['CMS_ACCESS_CMSMain'];
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
