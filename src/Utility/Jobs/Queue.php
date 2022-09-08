<?php

namespace TypeRocket\Utility\Jobs;

use TypeRocket\Utility\Jobs\Interfaces\JobCanQueue;
use TypeRocket\Utility\Jobs\Interfaces\AllowOneInSchedule;
use TypeRocket\Utility\Jobs\Interfaces\WithoutOverlapping;
use TypeRocket\Core\Config;
use TypeRocket\Core\Resolver;
use \ActionScheduler;
use \ActionScheduler_Store;

class Queue
{
    protected static array $registered = [];

    /**
     * Run jobs using action scheduler
     *
     * @param string $hookName
     * @param string $jobClass
     * @param array|string $data
     * @param int|string $actionId
     * @param \ActionScheduler_Action $action
     * @param $context
     * @return void
     * @throws \ReflectionException
     * @throws \Throwable
     */
    protected static function runJobFromActionScheduler($hookName, $jobClass, $data, $actionId, \ActionScheduler_Action $action, $context)
    {
        $data = tr_is_json($data) ? json_decode($data, true) : $data;
        $job = null;

        try {
            $job = Resolver::build($jobClass, ['payload' => $data]);

            if(!$job instanceof JobCanQueue) {
                throw new \Exception( sprintf( __('Job must be instance of %s.', 'typerocket-core'), JobCanQueue::class) );
            }

            $job->setActionSchedulerProperties($action, $actionId, $context);

            if($job instanceof WithoutOverlapping) {
                $ids = as_get_scheduled_actions([
                    'hook' => $hookName,
                    'status'   => [ActionScheduler_Store::STATUS_RUNNING]
                ], 'ids');

                if (($key = array_search($actionId, $ids)) !== false) {
                    unset($ids[$key]);
                }

                $time = null;
                if ( $scheduled_date = $action->get_schedule()->get_date() ) {
                    $time = (int) $scheduled_date->format( 'U' );
                }

                if( !empty($ids) && $job->willPostpone() ) {
                    $newActionId = static::addJob($job, $time);
                    $job->postponed($newActionId);
                    $message = sprintf( __('#%d job postponed as a new action #%d. Job %s only one can be in-progress at a time.', 'typerocket-core'), $actionId, $newActionId, $jobClass);
                    \ActionScheduler_Logger::instance()->log($actionId, $message);
                    \ActionScheduler_Logger::instance()->log($newActionId, sprintf( __("Created from postponed action #%d", 'typerocket-core'), $actionId) );
                    return;
                }
            }

            $job->handle();
        } catch (\Throwable $e) {
            \ActionScheduler_Logger::instance()->log($actionId, 'Error: ' . $e->getMessage());

            if($job instanceof JobCanQueue) {
                $job->failed([
                    'message' => $e->getMessage(),
                    'thrown' => $e,
                    'file' => $e->getFile(),
                    'payload' => $data,
                    'jobClass' => $jobClass,
                    'action' => $action,
                    'actionId' => $actionId,
                    'context' => $context,
                    'job' => $job,
                ]);
            }

            throw $e;
        }
    }

    /**
     * @param string $hook
     * @param array|null|string $args
     * @param string $group
     * @return false|int|null
     */
    public static function findScheduledJob($hook, $args = null, $group = '')
    {
        if ( ! ActionScheduler::is_initialized( __FUNCTION__ ) ) {
            return false;
        }

        $query_args = array(
            'hook'     => $hook,
            'status'   => array( ActionScheduler_Store::STATUS_RUNNING, ActionScheduler_Store::STATUS_PENDING ),
            'group'    => $group,
            'orderby'  => 'none',
        );

        if ( null !== $args ) {
            $query_args['args'] = $args;
        }

        return ActionScheduler::store()->query_action( $query_args );
    }

    /**
     * @param string $jobClass
     * @return void
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function registerJob(string $jobClass)
    {
        static::$registered[$jobClass] = $jobClass;
        $hook = 'typerocket_job.' . $jobClass;

        // Hook required but action run really run with action_scheduler_after_execute
        add_action($hook, '__return_true');

        /**
         * This need to happen before any other after execute
         * hooks fire because this is the execution.
         */
        add_action('action_scheduler_after_execute', function($action_id, $action, $context) use ($jobClass, $hook) {
            $hookName = $action->get_hook();

            if($hook === $hookName) {
                $args = $action->get_args();
                Queue::runJobFromActionScheduler($hookName, $jobClass, $args[0], $action_id, $action, $context);
            }
        }, 0, 3);
    }

    /**
     * @param JobCanQueue $job
     * @param null|int $time
     *
     * @return int
     */
    public static function addJob(JobCanQueue $job, $time = null)
    {
        if(Config::getFromContainer()->locate('queue.mode') === 'sync') {
            $job->handle();

            return -1;
        }

        $class = get_class($job);
        $time = $time ?? (time() + $job::DELAY);
        $actionName = 'typerocket_job.' . $class;

        if(!in_array($class, static::$registered)) {
            throw new \Error( sprintf( __("Job %s is not registered.", 'typerocket-core'), $actionName) );
        }

        if($job instanceof AllowOneInSchedule) {
            if ($firstFoundActionId = static::findScheduledJob($actionName) ) {
                $job->alreadyScheduled($firstFoundActionId);
                $message = sprintf( __("Attempted to add job %s but can only be queued once at any given time.", 'typerocket-core'), $actionName);
                \ActionScheduler_Logger::instance()->log($firstFoundActionId, $message);
                throw new \Error($message);
            }
        }

        return as_schedule_single_action( $time, $actionName, [$job->payload] );
    }
}