<?php

namespace TypeRocket\Utility\Jobs\Interfaces;

/**
 * Jobs that implement this class will only be added to the
 * scheduler if no other jobs of that job class exist.
 */
interface AllowOneInSchedule
{
    public function alreadyScheduled($firstFoundActionId);
}