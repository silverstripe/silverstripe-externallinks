<?php

/**
 * Represents a single link checked for a single run that is broken
 *
 * @method BrokenExternalPageTrack Track()
 * @method BrokenExternalPageTrackStatus Status()
 */
class BrokenExternalLink extends DataObject {

	private static $db = array(
		'Link' => 'Varchar(2083)', // 2083 is the maximum length of a URL in Internet Explorer.
		'HTTPCode' =>'Int'
	);

	private static $has_one = array(
		'Track' => 'BrokenExternalPageTrack',
		'Status' => 'BrokenExternalPageTrackStatus'
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
	public function Page() {
		return $this->Track()->Page();
	}

	public function canEdit($member = false) {
		return false;
	}

	public function canView($member = false) {
		$member = $member ? $member : Member::currentUser();
		$codes = array('content-authors', 'administrators');
		return Permission::checkMember($member, $codes);
	}

	/**
	 * Retrieve a human readable description of a response code
	 *
	 * @return string
	 */
	public function getHTTPCodeDescription() {
		$code = $this->HTTPCode;
		if(empty($code)) {
			// Assume that $code = 0 means there was no response
			$description = _t('BrokenExternalLink.NOTAVAILABLE', 'Server Not Available');
		} elseif(
			($descriptions = Config::inst()->get('SS_HTTPResponse', 'status_codes'))
			&& isset($descriptions[$code])
		) {
			$description = $descriptions[$code];
		} else {
			$description = _t('BrokenExternalLink.UNKNOWNRESPONSE', 'Unknown Response Code');
		}
		return sprintf("%d (%s)", $code, $description);
	}
}


