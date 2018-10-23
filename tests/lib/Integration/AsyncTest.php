<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use Carbon\Carbon;
use DummyApp\Download;
use DummyApp\Jobs\CreateDownload;
use DummyApp\Jobs\DeleteDownload;
use DummyApp\Jobs\ReplaceDownload;
use Illuminate\Support\Facades\Queue;

class AsyncTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'downloads';

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        Queue::fake();
        Carbon::setTestNow('2018-10-23 12:00:00.123456');
    }

    public function testCreate()
    {
        $data = [
            'type' => 'downloads',
            'attributes' => [
                'category' => 'my-posts',
            ],
        ];

        $this->doCreate($data)->assertStatus(202)->assertJson([
            'data' => [
                'type' => 'queue-jobs',
                'attributes' => [
                    'attempts' => 0,
                    'created-at' => Carbon::now()->format('Y-m-d\TH:i:s.uP'),
                    'completed-at' => null,
                    'failed' => null,
                    'resource' => 'downloads',
                    'status' => 'queued',
                    'timeout' => 60,
                    'timeout-at' => null,
                    'tries' => null,
                    'updated-at' => Carbon::now()->format('Y-m-d\TH:i:s.uP'),
                ],
            ],
        ]);

        $job = $this->assertDispatchedCreate();

        $this->assertTrue($job->wasClientDispatched(), 'was client dispatched');
        $this->assertSame('v1', $job->api(), 'api');
        $this->assertSame('downloads', $job->resourceType(), 'resource type');
        $this->assertNull($job->resourceId(), 'resource id');

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'created_at' => '2018-10-23 12:00:00.123456',
            'updated_at' => '2018-10-23 12:00:00.123456',
            'api' => 'v1',
            'resource_type' => 'downloads',
            'resource_id' => null,
            'completed_at' => null,
            'failed' => false,
            'status' => 'queued',
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

        $this->doCreate($data)->assertStatus(202)->assertJson([
            'data' => [
                'type' => 'queue-jobs',
                'attributes' => [
                    'resource' => 'downloads',
                    'timeout' => 60,
                    'timeout-at' => null,
                    'tries' => null,
                ],
            ],
        ]);

        $job = $this->assertDispatchedCreate();

        $this->assertSame($data['id'], $job->resourceId(), 'resource id');
        $this->assertNotSame($data['id'], $job->clientJob->getKey());

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'created_at' => '2018-10-23 12:00:00.123456',
            'updated_at' => '2018-10-23 12:00:00.123456',
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

        $this->doUpdate($data, ['include' => 'target'])->assertStatus(202)->assertJson([
            'data' => [
                'type' => 'queue-jobs',
                'attributes' => [
                    'resource' => 'downloads',
                    'timeout' => null,
                    'timeout-at' => Carbon::now()->addSeconds(25)->format('Y-m-d\TH:i:s.uP'),
                    'tries' => null,
                ],
                'relationships' => [
                    'target' => [
                        'data' => [
                            'type' => 'downloads',
                            'id' => (string) $download->getRouteKey(),
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'type' => 'downloads',
                    'id' => (string) $download->getRouteKey(),
                ],
            ],
        ]);

        $job = $this->assertDispatchedReplace();

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'created_at' => '2018-10-23 12:00:00.123456',
            'updated_at' => '2018-10-23 12:00:00.123456',
            'api' => 'v1',
            'resource_type' => 'downloads',
            'resource_id' => $download->getRouteKey(),
            'timeout' => null,
            'timeout_at' => '2018-10-23 12:00:25.123456',
            'tries' => null,
        ]);
    }

    public function testDelete()
    {
        $download = factory(Download::class)->create();

        $this->doDelete($download)->assertStatus(202)->assertJson([
            'data' => [
                'type' => 'queue-jobs',
                'attributes' => [
                    'resource' => 'downloads',
                    'timeout' => null,
                    'timeout-at' => null,
                    'tries' => 5,
                ],
            ],
        ]);

        $job = $this->assertDispatchedDelete();

        $this->assertDatabaseHas('json_api_client_jobs', [
            'uuid' => $job->clientJob->getKey(),
            'created_at' => '2018-10-23 12:00:00.123456',
            'updated_at' => '2018-10-23 12:00:00.123456',
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
