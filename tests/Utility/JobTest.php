<?php

namespace Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Utility\Jobs\Interfaces\AllowOneInSchedule;
use TypeRocket\Utility\Jobs\Interfaces\WithoutOverlapping;
use TypeRocket\Utility\Jobs\Job;
use TypeRocket\Utility\Jobs\Queue;

class JobTestClass extends Job implements AllowOneInSchedule, WithoutOverlapping { public function handle() {} }

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

    public function testJobPayloadJsonEncoded()
    {
        $payload = [
            ['name' => 'jim'],
            ['name' => 'kim'],
            ['name' => 'kat'],
        ];

        $job = new JobTestClass;
        $job->payload = json_encode($payload);
        $decoded = $job->payload;

        $this->assertTrue($decoded === $payload);
    }

    public function testJobProperties()
    {
        $payload = [
            ['name' => 'jim'],
            ['name' => 'kim'],
            ['name' => 'kat'],
        ];

        $job = new JobTestClass($payload);
        $job->someTestProp = null;

        $this->assertTrue($job->id === null);
        $this->assertTrue($job->action === null);
        $this->assertTrue($job->context === null);
        $this->assertTrue($job->delay === null);
        $this->assertTrue($job->someTestProp === null);
    }

    public function testQueueError()
    {
        try {
            Queue::addJob(new JobTestClass());
        } catch (\Throwable $e) {

        }

        $this->assertStringContainsString('Job typerocket_job.Utility\JobTestClass is not registered.', $e->getMessage());
    }
}