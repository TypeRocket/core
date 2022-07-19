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

    public function setActionSchedulerProperties(\ActionScheduler_Action $action, $actionId, $context)
    {
        $this->properties = array_merge($this->properties, [
            'action' => $action,
            'id' => $actionId,
            'context' => $context,
        ]);
    }

    public function willPostpone() : bool
    {
        return true;
    }

    public function postponed($newActionId)
    {
        // Do nothing by default
    }

    public function alreadyScheduled($firstFoundActionId)
    {
        // Do nothing by default
    }

    public function failed(array $data)
    {
        // Do nothing by default
    }

    public function logToAction(string $message) : int
    {
        return (int) \ActionScheduler_Logger::instance()->log($this->id, $message);
    }

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