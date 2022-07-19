<?php

namespace TypeRocket\Utility\Jobs\Interfaces;

interface AllowOneInSchedule
{
    public function alreadyScheduled($firstFoundActionId);
}