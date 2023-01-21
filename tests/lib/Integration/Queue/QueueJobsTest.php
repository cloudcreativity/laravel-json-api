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

use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;

class QueueJobsTest extends TestCase
{

    public function testListAll()
    {
        $jobs = factory(ClientJob::class, 2)->create();
        // this one should not appear in results as it is for a different resource type.
        factory(ClientJob::class)->create(['resource_type' => 'foo']);

        $response = $this
            ->jsonApi('queue-jobs')
            ->get('/api/v1/downloads/queue-jobs');

        $response
            ->assertFetchedMany($jobs);
    }

    public function testReadPending()
    {
        $job = factory(ClientJob::class)->create();
        $expected = $this->serialize($job);

        $response = $this
            ->jsonApi('queue-jobs')
            ->get($expected['links']['self']);

        $response
            ->assertFetchedOneExact($expected);
    }

    /**
     * When job process is done, the request SHOULD return a status 303 See other
     * with a link in Location header. The spec recommendation shows a response with
     * a Content-Type header as `application/vnd.api+json` but no content in the response body.
     */
    public function testReadNotPending()
    {
       $job = factory(ClientJob::class)->states('success', 'with_download')->create();
       $expected = $this->serialize($job);

        $response = $this
            ->jsonApi('queue-jobs')
            ->get($expected['links']['self']);

       $response
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
        $job = factory(ClientJob::class)->states('success')->create();
        $expected = $this->serialize($job);

        $response = $this
            ->jsonApi('queue-jobs')
            ->get($expected['links']['self']);

        $response
            ->assertFetchedOneExact($expected)
            ->assertHeaderMissing('Location');
    }

    /**
     * If the async process fails, we do not expect it to return a See Other even if
     * it has a resource id. This is because otherwise there is no way for the client
     * to know that it failed.
     */
    public function testReadFailed()
    {
        $job = factory(ClientJob::class)->states('failed', 'with_download')->create();
        $expected = $this->serialize($job);

        $response = $this
            ->jsonApi('queue-jobs')
            ->get($expected['links']['self']);

        $response
            ->assertFetchedOneExact($expected)
            ->assertHeaderMissing('Location');
    }

    public function testReadUnknownResourceType()
    {
        $job = factory(ClientJob::class)->create(['resource_type' => 'foo']);
        $expected = $this->serialize($job);

        $response = $this
            ->jsonApi('queue-jobs')
            ->get($expected['links']['self']);

        $response
            ->assertStatus(404);
    }

    public function testInvalidInclude()
    {
        $job = factory(ClientJob::class)->create();
        $expected = $this->serialize($job);

        $response = $this
            ->jsonApi('queue-jobs')
            ->includePaths('foo')
            ->get($expected['links']['self']);

        $response
            ->assertStatus(400);
    }

    /**
     * Get the expected resource object for a client job model.
     *
     * @param ClientJob $job
     * @return array
     */
    private function serialize(ClientJob $job): array
    {
        return [
            'type' => 'queue-jobs',
            'id' => (string) $job->getRouteKey(),
            'attributes' => [
                'attempts' => $job->attempts,
                'createdAt' => $job->created_at->toJSON(),
                'completedAt' => optional($job->completed_at)->toJSON(),
                'failed' => $job->failed,
                'resourceType' => 'downloads',
                'timeout' => $job->timeout,
                'timeoutAt' => optional($job->timeout_at)->toJSON(),
                'tries' => $job->tries,
                'updatedAt' => $job->updated_at->toJSON(),
            ],
            'links' => [
                'self' => url('/api/v1', [$job->resource_type, 'queue-jobs', $job]),
            ],
        ];
    }
}
