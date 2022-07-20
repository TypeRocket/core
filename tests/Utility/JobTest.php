<?php

namespace Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Utility\Jobs\Job;

class JobTestClass extends Job { public function handle() {} }

class JobTest extends TestCase
{
    public function testJobGetPayload()
    {
        $payload = [
            ['name' => 'jim'],
            ['name' => 'kim'],
            ['name' => 'kat'],
        ];

        $job = new JobTestClass($payload);

        $this->assertTrue($job->payload === $payload);
    }

    public function testJobPayload()
    {
        $payload = [
            ['name' => 'jim'],
            ['name' => 'kim'],
            ['name' => 'kat'],
        ];

        $job = new JobTestClass($payload);

        $this->assertTrue($job->payload === $payload);
    }
}