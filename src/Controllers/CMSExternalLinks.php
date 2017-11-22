<?php

class CMSExternalLinks_Controller extends Controller {

	private static $allowed_actions = array('getJobStatus', 'start');

	/*
	 * Respond to Ajax requests for info on a running job
	 *
	 * @return string JSON string detailing status of the job
	 */
	public function getJobStatus() {
		// Set headers
		HTTP::set_cache_age(0);
		HTTP::add_cache_headers($this->response);
		$this->response
			->addHeader('Content-Type', 'application/json')
			->addHeader('Content-Encoding', 'UTF-8')
			->addHeader('X-Content-Type-Options', 'nosniff');

		// Format status
		$track = BrokenExternalPageTrackStatus::get_latest();
		if($track) return json_encode(array(
			'TrackID' => $track->ID,
			'Status' => $track->Status,
			'Completed' => $track->getCompletedPages(),
			'Total' => $track->getTotalPages()
		));
	}


	/*
	 * Starts a broken external link check
	 */
	public function start() {
		// return if the a job is already running
		$status = BrokenExternalPageTrackStatus::get_latest();
		if ($status && $status->Status == 'Running') return;

		// Create a new job
		if (class_exists('QueuedJobService')) {
			// Force the creation of a new run
			BrokenExternalPageTrackStatus::create_status();
			$checkLinks = new CheckExternalLinksJob();
			singleton('QueuedJobService')->queueJob($checkLinks);
		} else {
			//TODO this hangs as it waits for the connection to be released
			// should return back and continue processing
			// http://us3.php.net/manual/en/features.connection-handling.php
			$task = CheckExternalLinksTask::create();
			$task->runLinksCheck();
		}
	}
}
