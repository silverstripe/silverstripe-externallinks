<?php

namespace SilverStripe\ExternalLinks\Controllers;

use SilverStripe\Admin\AdminController;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ExternalLinks\Model\BrokenExternalPageTrackStatus;
use SilverStripe\ExternalLinks\Jobs\CheckExternalLinksJob;
use SilverStripe\ExternalLinks\Tasks\CheckExternalLinksTask;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use SilverStripe\PolyExecution\PolyOutput;

class CMSExternalLinksController extends AdminController
{
    private static ?string $url_segment = 'externallinks';

    private static string|array $required_permission_codes = [
        'CMS_ACCESS_CMSMain',
    ];

    private static $allowed_actions = [
        'getJobStatus',
        'start'
    ];

    /**
     * Respond to Ajax requests for info on a running job
     */
    public function getJobStatus(): HTTPResponse
    {
        $this->getResponse()->addHeader('X-Content-Type-Options', 'nosniff');
        $track = BrokenExternalPageTrackStatus::get_latest();
        if ($track) {
            return $this->jsonSuccess(200, [
                'TrackID' => $track->ID,
                'Status' => $track->Status,
                'Completed' => $track->getCompletedPages(),
                'Total' => $track->getTotalPages()
            ]);
        }
        return $this->jsonSuccess(200, []);
    }

    /**
     * Starts a broken external link check
     */
    public function start(): HTTPResponse
    {
        // return if the a job is already running
        $status = BrokenExternalPageTrackStatus::get_latest();
        if ($status && $status->Status == 'Running') {
            return $this->jsonSuccess(200, []);
        }

        // Create a new job
        if (class_exists(QueuedJobService::class)) {
            // Force the creation of a new run
            BrokenExternalPageTrackStatus::create_status();
            $checkLinks = new CheckExternalLinksJob();
            singleton(QueuedJobService::class)->queueJob($checkLinks);
        } else {
            $task = CheckExternalLinksTask::create();
            $task->runLinksCheck(PolyOutput::create(PolyOutput::FORMAT_HTML));
        }
        return $this->jsonSuccess(200, []);
    }
}
