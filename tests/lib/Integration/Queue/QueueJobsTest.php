<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Queue;

use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;

class QueueJobsTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'queue-jobs';

    public function testListAll()
    {
        $jobs = factory(ClientJob::class, 2)->create();
        // this one should not appear in results as it is for a different resource type.
        factory(ClientJob::class)->create(['resource_type' => 'foo']);

        $this->getJsonApi('/api/v1/downloads/queue-jobs')
            ->assertFetchedMany($jobs);
    }

    public function testReadPending()
    {
        $job = factory(ClientJob::class)->create();
        $expected = $this->serialize($job);

        $this->getJsonApi($expected['links']['self'])
            ->assertFetchedOneExact($expected);
    }

    /**
     * When job process is done, the request SHOULD return a status 303 See other
     * with a link in Location header. The spec recommendation shows a response with
     * a Content-Type header as `application/vnd.api+json` but no content in the response body.
     */
    public function testReadNotPending()
    {
       $job = factory(ClientJob::class)->states('success')->create();

       $response = $this
           ->getJsonApi($this->jobUrl($job))
           ->assertStatus(303)
           ->assertHeader('Location', url('/api/v1/downloads', [$job->resource_id]))
           ->assertHeader('Content-Type', 'application/vnd.api+json');

       $this->assertEmpty($response->getContent(), 'content is empty.');
    }

    /**
     * If the asynchronous process does not have a location, a See Other response cannot be
     * returned. In this scenario, we expect the job to be serialized.
     */
    public function testReadNotPendingCannotSeeOther()
    {
        $job = factory(ClientJob::class)->states('success')->create(['resource_id' => null]);
        $expected = $this->serialize($job);

        $this->getJsonApi($this->jobUrl($job))
            ->assertFetchedOneExact($expected)
            ->assertHeaderMissing('Location');
    }

    public function testReadNotFound()
    {
        $job = factory(ClientJob::class)->create(['resource_type' => 'foo']);

        $this->getJsonApi($this->jobUrl($job, 'downloads'))
            ->assertStatus(404);
    }

    public function testInvalidInclude()
    {
        $job = factory(ClientJob::class)->create();

        $this->getJsonApi($this->jobUrl($job) . '?' . http_build_query(['include' => 'foo']))
            ->assertStatus(400);
    }

    /**
     * @param ClientJob $job
     * @param string|null $resourceType
     * @return string
     */
    private function jobUrl(ClientJob $job, string $resourceType = null): string
    {
        return url('/api/v1', [
            $resourceType ?: $job->resource_type,
            'queue-jobs',
            $job
        ]);
    }

    /**
     * Get the expected resource object for a client job model.
     *
     * @param ClientJob $job
     * @return array
     */
    private function serialize(ClientJob $job): array
    {
        $self = $this->jobUrl($job);

        return [
            'type' => 'queue-jobs',
            'id' => (string) $job->getRouteKey(),
            'attributes' => [
                'attempts' => $job->attempts,
                'created-at' => $job->created_at->toAtomString(),
                'completed-at' => $job->completed_at ? $job->completed_at->toAtomString() : null,
                'failed' => $job->failed,
                'resource-type' => 'downloads',
                'timeout' => $job->timeout,
                'timeout-at' => $job->timeout_at ? $job->timeout_at->toAtomString() : null,
                'tries' => $job->tries,
                'updated-at' => $job->updated_at->toAtomString(),
            ],
            'links' => [
                'self' => $self,
            ],
        ];
    }
}
