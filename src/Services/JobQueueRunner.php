<?php
namespace TypeRocket\Services;

use TypeRocket\Core\Config;
use TypeRocket\Utility\Jobs\Queue;

class JobQueueRunner extends Service
{
    public const ALIAS = 'tr-job-queue';

    protected $singleton = true;

    public function __construct()
    {
        $queueConfig = Config::getFromContainer()->locate('queue') ?? [];

        require_once ( Config::getFromContainer()->locate('paths.vendor') . "/woocommerce/action-scheduler/action-scheduler.php" );

        $queueConfigActionScheduler = $queueConfig['action_scheduler'];
        add_filter( 'action_scheduler_retention_period', function($time) use ($queueConfigActionScheduler) {
            return (int) $queueConfigActionScheduler['retention_period'];
        });

        add_filter('action_scheduler_failure_period', function($time) use ($queueConfigActionScheduler) {
            return (int) $queueConfigActionScheduler['failure_period'];
        });

        add_filter('action_scheduler_timeout_period', function($time) use ($queueConfigActionScheduler) {
            return (int) $queueConfigActionScheduler['timeout_period'];
        });

        /**
         * array $jobList
         */
        foreach ($queueConfig['jobs'] as $jobClass) {
            Queue::registerJob($jobClass);
        }
    }

    public function register() : Service
    {
        return $this;
    }
}