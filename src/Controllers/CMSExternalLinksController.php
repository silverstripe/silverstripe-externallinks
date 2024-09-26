<?php

namespace SilverStripe\ExternalLinks\Controllers;

use SilverStripe\ExternalLinks\Model\BrokenExternalPageTrackStatus;
use SilverStripe\ExternalLinks\Jobs\CheckExternalLinksJob;
use SilverStripe\ExternalLinks\Tasks\CheckExternalLinksTask;
use SilverStripe\Control\Controller;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Security\Permission;

class CMSExternalLinksController extends Controller
{
    private static $allowed_actions = [
        'getJobStatus',
        'start'
    ];

    /**
     * Respond to Ajax requests for info on a running job
     *
     * @return string JSON string detailing status of the job
     */
    public function getJobStatus()
    {
        if (!Permission::check('CMS_ACCESS_CMSMain')) {
            return $this->httpError(403, 'You do not have permission to access this resource');
        }
        // Set headers
        HTTPCacheControlMiddleware::singleton()->setMaxAge(0);
        $this->response
            ->addHeader('Content-Type', 'application/json')
            ->addHeader('Content-Encoding', 'UTF-8')
            ->addHeader('X-Content-Type-Options', 'nosniff');

        // Format status
        $track = BrokenExternalPageTrackStatus::get_latest();
        if ($track) {
            return json_encode([
                'TrackID' => $track->ID,
                'Status' => $track->Status,
                'Completed' => $track->getCompletedPages(),
                'Total' => $track->getTotalPages()
            ]);
        }
    }

    /**
     * Starts a broken external link check
     */
    public function start()
    {
        if (!Permission::check('CMS_ACCESS_CMSMain')) {
            return $this->httpError(403, 'You do not have permission to access this resource');
        }
        // return if the a job is already running
        $status = BrokenExternalPageTrackStatus::get_latest();
        if ($status && $status->Status == 'Running') {
            return;
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
    }
}
