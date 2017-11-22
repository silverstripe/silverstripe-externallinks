<?php

/**
 * Represents the status of a track run
 *
 * @method DataList TrackedPages()
 * @method DataList BrokenLinks()
 * @property int $TotalPages Get total pages count
 * @property int $CompletedPages Get completed pages count
 */
class BrokenExternalPageTrackStatus extends DataObject {

	private static $db = array(
		'Status' => 'Enum("Completed, Running", "Running")',
		'JobInfo' => 'Varchar(255)'
	);

	private static $has_many = array(
		'TrackedPages' => 'BrokenExternalPageTrack',
		'BrokenLinks' => 'BrokenExternalLink'
	);

	/**
	 * Get the latest track status
	 *
	 * @return self
	 */
	public static function get_latest() {
		return self::get()
			->sort('ID', 'DESC')
			->first();
	}

	/**
	 * Gets the list of Pages yet to be checked
	 *
	 * @return DataList
	 */
	public function getIncompletePageList() {
		$pageIDs = $this
			->getIncompleteTracks()
			->column('PageID');
		if($pageIDs) return Versioned::get_by_stage('SiteTree', 'Stage')
			->byIDs($pageIDs);
	}

	/**
	 * Get the list of incomplete BrokenExternalPageTrack
	 *
	 * @return DataList
	 */
	public function getIncompleteTracks() {
		return $this
			->TrackedPages()
			->filter('Processed', 0);
	}

	/**
	 * Get total pages count
	 */
	public function getTotalPages() {
		return $this->TrackedPages()->count();
	}

	/**
	 * Get completed pages count
	 */
	public function getCompletedPages() {
		return $this
			->TrackedPages()
			->filter('Processed', 1)
			->count();
	}

	/**
	 * Returns the latest run, or otherwise creates a new one
	 *
	 * @return self
	 */
	public static function get_or_create() {
		// Check the current status
		$status = self::get_latest();
		if ($status && $status->Status == 'Running') {
			$status->updateStatus();
			return $status;
		}

		return self::create_status();
	}

	/*
	 * Create and prepare a new status
	 *
	 * @return self
	 */
	public static function create_status() {
		// If the script is to be started create a new status
		$status = self::create();
		$status->updateJobInfo('Creating new tracking object');

		// Setup all pages to test
		$pageIDs = Versioned::get_by_stage('SiteTree', 'Stage')
			->column('ID');
		foreach ($pageIDs as $pageID) {
			$trackPage = BrokenExternalPageTrack::create();
			$trackPage->PageID = $pageID;
			$trackPage->StatusID = $status->ID;
			$trackPage->write();
		}

		return $status;
	}

	public function updateJobInfo($message) {
		$this->JobInfo = $message;
		$this->write();
	}

	/**
	 * Self check status
	 */
	public function updateStatus() {
		if ($this->CompletedPages == $this->TotalPages) {
			$this->Status = 'Completed';
			$this->updateJobInfo('Setting to completed');
		}
	}
}