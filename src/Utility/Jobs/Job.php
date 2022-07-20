<?php

namespace TypeRocket\Utility\Jobs;

use TypeRocket\Utility\Jobs\Interfaces\JobCanQueue;

/**
 * @property mixed $payload
 * @property int $id
 * @property int $delay
 * @property null|\ActionScheduler_Action $action
 * @property null|string $context
 */
abstract class Job implements JobCanQueue
{
    const DELAY = 0;

    /**
     * @var null[]|string[]|int[]
     */
    protected $properties = [
        'payload' => null,
        'action' => null,
        'id' => null,
        'context' => null,
        'delay' => null,
    ];

    /**
     * Payload will be json encoded by __set and then
     * stored in the database by ActionScheduler.
     *
     * @param array $payload
     */
    public function __construct(array $payload = [])
    {
        $this->properties['payload'] = $payload;
    }

    /**
     * @param \ActionScheduler_Action $action
     * @param $actionId
     * @param $context
     * @return void
     */
    public function setActionSchedulerProperties(\ActionScheduler_Action $action, $actionId, $context)
    {
        $this->properties = array_merge($this->properties, [
            'action' => $action,
            'id' => $actionId,
            'context' => $context,
        ]);
    }

    /**
     * @return bool
     */
    public function willPostpone() : bool
    {
        return true;
    }

    /**
     * @param $newActionId
     * @return void
     */
    public function postponed($newActionId)
    {
        // Do nothing by default
    }

    /**
     * @param $firstFoundActionId
     * @return void
     */
    public function alreadyScheduled($firstFoundActionId)
    {
        // Do nothing by default
    }

    /**
     * @param array $data
     * @return void
     */
    public function failed(array $data)
    {
        // Do nothing by default
    }

    /**
     * @param string $message
     * @return int
     */
    public function logToAction(string $message) : int
    {
        return (int) \ActionScheduler_Logger::instance()->log($this->id, $message);
    }

    /**
     * Look to properties for job and action data
     *
     * @param string $key
     * @return int|mixed|string|null
     */
    public function __get($key)
    {
        if($key === 'payload') {
            $json = $this->properties['payload'] ?? null;
            return tr_is_json($json) ? json_decode($json, true) : $json;
        }

        if(in_array($key, ['id', 'action', 'context'])) {
            return $this->properties[$key];
        }

        return $this->{$key};
    }

    /**
     * Disable the ability to set action related data
     *
     * @param string $key
     * @param $value
     * @return void
     */
    public function __set($key, $value)
    {
        if($key === 'payload') {
            $this->properties['payload'] = tr_is_json($value) ? $value : json_encode($value);
        }

        if(in_array($key, ['id', 'action', 'context'])) {
            return;
        }

        $this->{$key} = $value;
    }

}