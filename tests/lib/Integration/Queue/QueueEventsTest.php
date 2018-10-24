<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Queue;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;

class QueueEventsTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        Carbon::setTestNow('2018-10-23 12:00:00.123456');
    }

    public function testCompletes()
    {
        $job = new TestJob();
        $job->clientJob = factory(ClientJob::class)->create();

        dispatch($job);

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'attempts' => 1,
            'completed_at' => Carbon::now()->format('Y-m-d H:i:s.u'),
            'failed' => false,
        ]);
    }

    public function testFails()
    {
        $job = new TestJob();
        $job->ex = true;
        $job->clientJob = factory(ClientJob::class)->create();

        try {
            dispatch($job);
            $this->fail('No exception thrown.');
        } catch (\LogicException $ex) {
            // no-op
        }

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'attempts' => 1,
            'completed_at' => Carbon::now()->format('Y-m-d H:i:s.u'),
            'failed' => true,
        ]);
    }
}
