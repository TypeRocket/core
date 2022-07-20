<?php
namespace TypeRocket\Utility\Jobs\Interfaces;

/**
 * Only jobs that implement this class can be queued and run
 */
interface JobCanQueue
{
    public function setActionSchedulerProperties(\ActionScheduler_Action $action, $actionId, $context);
    public function handle();
    public function failed(array $data);
}