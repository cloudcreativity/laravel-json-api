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

use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Jobs\CreateDownload;
use DummyApp\JsonApi\QueueJobs;

class CustomiseTest extends TestCase
{

    /**
     * Set to false as we need to override config before
     *
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * @var string
     */
    protected $resourceType = 'client-jobs';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('json-api-v1.jobs', [
            'resource' => $this->resourceType,
            'model' => CustomJob::class,
        ]);

        $this->app->bind('DummyApp\JsonApi\ClientJobs\Adapter', CustomAdapter::class);
        $this->app->bind('DummyApp\JsonApi\ClientJobs\Schema', QueueJobs\Schema::class);
        $this->app->bind('DummyApp\JsonApi\ClientJobs\Validators', QueueJobs\Validators::class);

        $this->withAppRoutes();
    }

    public function testListAll()
    {
        $jobs = factory(ClientJob::class, 2)->create();
        // this one should not appear in results as it is for a different resource type.
        factory(ClientJob::class)->create(['resource_type' => 'foo']);

        $this->getJsonApi('/api/v1/downloads/client-jobs')
            ->assertFetchedMany($jobs);
    }

    public function testPendingDispatch()
    {
        $async = CreateDownload::client('test')
            ->setResource('downloads')
            ->dispatch();

        $this->assertInstanceOf(CustomJob::class, $async);
    }
}
