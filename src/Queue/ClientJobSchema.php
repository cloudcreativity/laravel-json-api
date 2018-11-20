<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use DateTime;
use Neomerx\JsonApi\Schema\SchemaProvider;

class ClientJobSchema extends SchemaProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'queue-jobs';

    /**
     * @var string
     */
    protected $dateFormat = DateTime::ATOM;

    /**
     * @param ClientJob $resource
     * @return string
     */
    public function getId($resource)
    {
        return $resource->getRouteKey();
    }

    /**
     * @param ClientJob $resource
     * @return array
     */
    public function getAttributes($resource)
    {
        /** @var DateTime|null $completedAt */
        $completedAt = $resource->completed_at;
        /** @var DateTime|null $timeoutAt */
        $timeoutAt = $resource->timeout_at;

        return [
            'attempts' => $resource->attempts,
            'created-at' => $resource->created_at->format($this->dateFormat),
            'completed-at' => $completedAt ? $completedAt->format($this->dateFormat) : null,
            'failed' => $resource->failed,
            'resource-type' => $resource->resource_type,
            'timeout' => $resource->timeout,
            'timeout-at' => $timeoutAt ? $timeoutAt->format($this->dateFormat) : null,
            'tries' => $resource->tries,
            'updated-at' => $resource->updated_at->format($this->dateFormat),
        ];
    }

    /**
     * @param ClientJob|null $resource
     * @return string
     */
    public function getSelfSubUrl($resource = null)
    {
        if (!$resource) {
            return parent::getSelfSubUrl();
        }

        return sprintf(
            '/%s/%s/%s',
            $resource->resource_type,
            $this->getResourceType(),
            $this->getId($resource)
        );
    }

}
