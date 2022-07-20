<?php

namespace TypeRocket\Utility\Jobs\Interfaces;

/**
 * Jobs that implement this class will be added to the scheduler
 * any number of times but will execute one at a time.
 */
interface WithoutOverlapping
{
    public function postponed($newActionId);
    public function willPostpone() : bool;
}