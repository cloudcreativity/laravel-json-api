<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Resources\QueueJobs;

use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use DateTime;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
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
