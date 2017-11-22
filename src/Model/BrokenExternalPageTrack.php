<?php

namespace SilverStripe\ExternalLinks\Model;

use DataObject;
use Versioned;


/**
 * Represents a track for a single page
 */
class BrokenExternalPageTrack extends DataObject {

	private static $db = array(
		'Processed' => 'Boolean'
	);

	private static $has_one = array(
		'Page' => 'SiteTree',
		'Status' => 'BrokenExternalPageTrackStatus'
	);

	private static $has_many = array(
		'BrokenLinks' => 'BrokenExternalLink'
	);

	/**
	 * @return SiteTree
	 */
	public function Page() {
		return Versioned::get_by_stage('SiteTree', 'Stage')
			->byID($this->PageID);
	}
}
