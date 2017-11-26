<?php

namespace SilverStripe\ExternalLinks\Jobs;

use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;
use SilverStripe\ExternalLinks\Tasks\CheckExternalLinksTask;

if (!class_exists(AbstractQueuedJob::class)) {
    return;
}

/**
 * A Job for running a external link check for published pages
 *
 */
class CheckExternalLinksJob extends AbstractQueuedJob implements QueuedJob
{

    public function getTitle()
    {
        return _t(__CLASS__ . '.TITLE', 'Checking for external broken links');
    }

    public function getJobType()
    {
        return QueuedJob::QUEUED;
    }

    public function getSignature()
    {
        return md5(get_class($this));
    }

    /**
     * Check an individual page
     */
    public function process()
    {
        $task = CheckExternalLinksTask::create();
        $track = $task->runLinksCheck(1);
        $this->currentStep = $track->CompletedPages;
        $this->totalSteps = $track->TotalPages;
        $this->isComplete = $track->Status === 'Completed';
    }
}
