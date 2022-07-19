<?php

namespace TypeRocket\Utility\Jobs\Interfaces;

interface WithoutOverlapping
{
    public function postponed($newActionId);
    public function willPostpone() : bool;
}