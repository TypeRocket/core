<?php

namespace Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Utility\Jobs\Interfaces\AllowOneInSchedule;
use TypeRocket\Utility\Jobs\Interfaces\WithoutOverlapping;
use TypeRocket\Utility\Jobs\Job;
use TypeRocket\Utility\Jobs\Queue;

class JobTestClass extends Job implements AllowOneInSchedule { public function handle() {} }
class JobNoOverlapTestClass extends Job implements WithoutOverlapping { public function handle() {

}}

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

    public function testJobDispatch()
    {
        Queue::cancelJobs(JobTestClass::class);
        Queue::removeJobsAll();

        Queue::registerJob(JobTestClass::class);

        $r = JobTestClass::dispatch([
            ['name' => 'jim'],
            ['name' => 'kim'],
            ['name' => 'kat'],
        ]);

        try {
            JobTestClass::dispatch();
        } catch (\Error $e) {
            $this->assertStringContainsString('Attempted to add job typerocket_job.Utility\JobTestClass but can only be queued once at any given time.', $e->getMessage());
        }

        $job = Queue::findScheduledJob('typerocket_job.'.JobTestClass::class);

        $this->assertTrue(1 === Queue::run());
        $this->assertTrue($r === $job);
    }

    public function testJobDispatchWithoutOverlapWithoutCalling_runJobFromActionScheduler()
    {
        global $wpdb;

        Queue::cancelJobs(JobNoOverlapTestClass::class);
        Queue::removeJobsAll();

        Queue::registerJob(JobNoOverlapTestClass::class);

        $r = JobNoOverlapTestClass::dispatch([
            ['name' => 'jim'],
            ['name' => 'kim'],
            ['name' => 'kat'],
        ]);

        $e = null;

        try {
            $w = JobNoOverlapTestClass::dispatch();
        } catch (\Error $e) {

        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'actionscheduler_actions',
            ['status' => 'in-progress'], // Data to update
            ['action_id' => $w ] // Where clause
        );

        $ids = as_get_scheduled_actions([
            'hook' => 'typerocket_job.'.JobNoOverlapTestClass::class,
            'status'   => [\ActionScheduler_Store::STATUS_RUNNING]
        ], 'ids');

        $this->assertTrue($e === null);
        $this->assertTrue($updated === 1);
        $this->assertTrue(1 === Queue::run());
        $this->assertTrue(in_array($w, $ids) && count($ids) === 1);
    }
}