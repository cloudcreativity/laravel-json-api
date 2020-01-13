<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Download;
use DummyApp\Jobs\CreateDownload;
use DummyApp\Jobs\DeleteDownload;
use DummyApp\Jobs\ReplaceDownload;
use DummyApp\JsonApi\Downloads\Adapter;
use Illuminate\Support\Facades\Queue;

class ControllerHooksTest extends TestCase
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
        $this->app->bind(JsonApiController::class, Controller::class);

        $mock = $this
            ->getMockBuilder(Adapter::class)
            ->setConstructorArgs([new StandardStrategy()])
            ->setMethods(['create', 'update','delete'])
            ->getMock();

        $mock->expects($this->never())->method('create');
        $mock->expects($this->never())->method('update');
        $mock->expects($this->never())->method('delete');

        $this->app->instance(Adapter::class, $mock);
    }

    public function testCreate()
    {
        $data = [
            'type' => 'downloads',
            'attributes' => [
                'category' => 'my-posts',
            ],
        ];

        $this->doCreate($data)->assertAcceptedWithId(
            'http://localhost/api/v1/downloads/queue-jobs',
            ['type' => 'queue-jobs']
        );

        $job = $this->assertDispatchedCreate();

        $this->assertTrue($job->wasClientDispatched(), 'was client dispatched');
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

        $this->doUpdate($data)->assertAcceptedWithId(
            'http://localhost/api/v1/downloads/queue-jobs',
            ['type' => 'queue-jobs']
        );

        $job = $this->assertDispatchedReplace();

        $this->assertTrue($job->wasClientDispatched(), 'was client dispatched.');
    }

    public function testDelete()
    {
        $download = factory(Download::class)->create();

        $this->doDelete($download)->assertAcceptedWithId(
            'http://localhost/api/v1/downloads/queue-jobs',
            ['type' => 'queue-jobs']
        );

        $job = $this->assertDispatchedDelete();

        $this->assertTrue($job->wasClientDispatched(), 'was client dispatched.');
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
