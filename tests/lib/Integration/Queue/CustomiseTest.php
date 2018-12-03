<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Queue;

use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use DummyApp\Jobs\CreateDownload;
use DummyApp\JsonApi\QueueJobs;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;

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
    protected function setUp()
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
