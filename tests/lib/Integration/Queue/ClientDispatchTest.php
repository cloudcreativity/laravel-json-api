<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Download;
use DummyApp\Jobs\CreateDownload;
use DummyApp\Jobs\DeleteDownload;
use DummyApp\Jobs\ReplaceDownload;
use Illuminate\Support\Facades\Queue;

class ClientDispatchTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'downloads';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Carbon::setTestNow('2018-10-23 12:00:00');
    }

    public function testCreate()
    {
        $data = [
            'type' => 'downloads',
            'attributes' => [
                'category' => 'my-posts',
            ],
        ];

        $expected = [
            'type' => 'queue-jobs',
            'attributes' => [
                'attempts' => 0,
                'created-at' => Carbon::now()->toAtomString(),
                'completed-at' => null,
                'failed' => false,
                'resource-type' => 'downloads',
                'timeout' => 60,
                'timeout-at' => null,
                'tries' => null,
                'updated-at' => Carbon::now()->toAtomString(),
            ],
        ];

        $id = $this->doCreate($data)->assertAcceptedWithId(
            'http://localhost/api/v1/downloads/queue-jobs',
            $expected
        )->jsonApi('/data/id');

        $job = $this->assertDispatchedCreate();

        $this->assertTrue($job->wasClientDispatched(), 'was client dispatched');
        $this->assertSame('v1', $job->api(), 'api');
        $this->assertSame('downloads', $job->resourceType(), 'resource type');
        $this->assertNull($job->resourceId(), 'resource id');

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $id,
            'created_at' => '2018-10-23 12:00:00',
            'updated_at' => '2018-10-23 12:00:00',
            'api' => 'v1',
            'resource_type' => 'downloads',
            'resource_id' => null,
            'completed_at' => null,
            'failed' => false,
            'attempts' => 0,
            'timeout' => 60,
            'timeout_at' => null,
            'tries' => null,
        ]);
    }

    /**
     * If we are asynchronously creating a resource with a client generated id,
     * that id needs to be stored on the client job.
     */
    public function testCreateWithClientGeneratedId()
    {
        $data = [
            'type' => 'downloads',
            'id' => '85f3cb08-5c5c-4e41-ae92-57097d28a0b8',
            'attributes' => [
                'category' => 'my-posts',
            ],
        ];

        $this->doCreate($data)->assertAcceptedWithId('http://localhost/api/v1/downloads/queue-jobs', [
            'type' => 'queue-jobs',
            'attributes' => [
                'resource-type' => 'downloads',
                'timeout' => 60,
                'timeout-at' => null,
                'tries' => null,
            ],
        ]);

        $job = $this->assertDispatchedCreate();

        $this->assertSame($data['id'], $job->resourceId(), 'resource id');
        $this->assertNotSame($data['id'], $job->clientJob->getKey());

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'created_at' => '2018-10-23 12:00:00',
            'updated_at' => '2018-10-23 12:00:00',
            'api' => 'v1',
            'resource_type' => 'downloads',
            'resource_id' => $data['id'],
            'timeout' => 60,
            'timeout_at' => null,
            'tries' => null,
        ]);
    }

    public function testUpdate()
    {
        $download = factory(Download::class)->create(['category' => 'my-posts']);

        $data = [
            'type' => 'downloads',
            'id' => (string) $download->getRouteKey(),
            'attributes' => [
                'category' => 'my-comments',
            ],
        ];

        $expected = [
            'type' => 'queue-jobs',
            'attributes' => [
                'resource-type' => 'downloads',
                'timeout' => null,
                'timeout-at' => Carbon::now()->addSeconds(25)->toAtomString(),
                'tries' => null,
            ],
        ];

        $this->doUpdate($data)->assertAcceptedWithId(
            'http://localhost/api/v1/downloads/queue-jobs',
            $expected
        );

        $job = $this->assertDispatchedReplace();

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'created_at' => '2018-10-23 12:00:00',
            'updated_at' => '2018-10-23 12:00:00',
            'api' => 'v1',
            'resource_type' => 'downloads',
            'resource_id' => $download->getRouteKey(),
            'timeout' => null,
            'timeout_at' => '2018-10-23 12:00:25',
            'tries' => null,
        ]);
    }

    public function testDelete()
    {
        $download = factory(Download::class)->create();

        $this->doDelete($download)->assertAcceptedWithId('http://localhost/api/v1/downloads/queue-jobs', [
            'type' => 'queue-jobs',
            'attributes' => [
                'resource-type' => 'downloads',
                'timeout' => null,
                'timeout-at' => null,
                'tries' => 5,
            ],
        ]);

        $job = $this->assertDispatchedDelete();

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'created_at' => '2018-10-23 12:00:00',
            'updated_at' => '2018-10-23 12:00:00',
            'api' => 'v1',
            'resource_type' => 'downloads',
            'resource_id' => $download->getRouteKey(),
            'tries' => 5,
            'timeout' => null,
            'timeout_at' => null,
        ]);
    }

    /**
     * @return CreateDownload
     */
    private function assertDispatchedCreate(): CreateDownload
    {
        $actual = null;

        Queue::assertPushed(CreateDownload::class, function ($job) use (&$actual) {
            $actual = $job;

            return $job->clientJob->exists;
        });

        return $actual;
    }

    /**
     * @return ReplaceDownload
     */
    private function assertDispatchedReplace(): ReplaceDownload
    {
        $actual = null;

        Queue::assertPushed(ReplaceDownload::class, function ($job) use (&$actual) {
            $actual = $job;

            return $job->clientJob->exists;
        });

        return $actual;
    }

    /**
     * @return DeleteDownload
     */
    private function assertDispatchedDelete(): DeleteDownload
    {
        $actual = null;

        Queue::assertPushed(DeleteDownload::class, function ($job) use (&$actual) {
            $actual = $job;

            return $job->clientJob->exists;
        });

        return $actual;
    }
}
