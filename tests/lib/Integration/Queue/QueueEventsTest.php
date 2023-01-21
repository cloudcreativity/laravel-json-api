<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Queue;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Download;

class QueueEventsTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2018-10-23 12:00:00.123456');
    }

    public function testCompletes(): void
    {
        $job = new TestJob();
        $job->clientJob = factory(ClientJob::class)->create();

        dispatch($job);

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'attempts' => 1,
            'completed_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'failed' => false,
        ]);

        $clientJob = $job->clientJob->refresh();

        $this->assertInstanceOf(Download::class, $clientJob->getResource());
    }

    public function testFails(): void
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
            'completed_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'failed' => true,
        ]);
    }

    public function testDoesNotCauseException(): void
    {
        $job = new TestJob();
        $job->model = factory(Download::class)->create();
        $job->clientJob = factory(ClientJob::class)->create();

        dispatch($job);

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'attempts' => 1,
            'completed_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'failed' => false,
        ]);
    }
}
