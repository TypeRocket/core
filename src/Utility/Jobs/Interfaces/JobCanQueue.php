<?php
namespace TypeRocket\Utility\Jobs\Interfaces;

interface JobCanQueue
{
    public function setActionSchedulerProperties(\ActionScheduler_Action $action, $actionId, $context);
    public function handle();
    public function failed(array $data);
}