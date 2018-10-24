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

    public function testRead()
    {
        $job = factory(ClientJob::class)->create();
        $expected = $this->serialize($job);

        $this->getJsonApi($expected['links']['self'])
            ->assertRead($expected);
    }

    public function testReadNotFound()
    {
        $job = factory(ClientJob::class)->create(['resource_type' => 'foo']);

        $this->getJsonApi($this->selfUrl($job, 'downloads'))
            ->assertStatus(404);
    }

    /**
     * @param ClientJob $job
     * @param string|null $resourceType
     * @return string
     */
    private function selfUrl(ClientJob $job, string $resourceType = null): string
    {
        $resourceType = $resourceType ?: $job->resource_type;

        return "http://localhost/api/v1/{$resourceType}/queue-jobs/{$job->getRouteKey()}";
    }

    /**
     * Get the expected resource object for a client job model.
     *
     * @param ClientJob $job
     * @return array
     */
    private function serialize(ClientJob $job): array
    {
        $self = $this->selfUrl($job);
        $format = 'Y-m-d\TH:i:s.uP';

        return [
            'type' => 'queue-jobs',
            'id' => (string) $job->getRouteKey(),
            'attributes' => [
                'attempts' => $job->attempts,
                'created-at' => $job->created_at->format($format),
                'completed-at' => $job->completed_at ? $job->completed_at->format($format) : null,
                'failed' => $job->failed,
                'resource' => 'downloads',
                'timeout' => $job->timeout,
                'timeout-at' => $job->timeout_at ? $job->timeout_at->format($format) : null,
                'tries' => $job->tries,
                'updated-at' => $job->updated_at->format($format),
            ],
            'relationships' => [
                'target' => [
                    'links' => [
                        'self' => "{$self}/relationships/target",
                        'related' => "{$self}/target",
                    ],
                ],
            ],
            'links' => [
                'self' => $self,
            ],
        ];
    }
}
