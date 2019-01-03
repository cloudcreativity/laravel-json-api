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

namespace DummyApp\JsonApi\QueueJobs;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Queue\AsyncSchema;
use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    use AsyncSchema;

    /**
     * @param ClientJob $resource
     * @return string
     */
    public function getId($resource)
    {
        return (string) $resource->getRouteKey();
    }

    /**
     * @param ClientJob $resource
     * @return array
     */
    public function getAttributes($resource)
    {
        /** @var Carbon|null $completedAt */
        $completedAt = $resource->completed_at;
        /** @var Carbon|null $timeoutAt */
        $timeoutAt = $resource->timeout_at;

        return [
            'attempts' => $resource->attempts,
            'completed-at' => $completedAt ? $completedAt->toAtomString() : null,
            'created-at' => $resource->created_at->toAtomString(),
            'failed' => $resource->failed,
            'resource-type' => $resource->resource_type,
            'timeout' => $resource->timeout,
            'timeout-at' => $timeoutAt ? $timeoutAt->toAtomString() : null,
            'tries' => $resource->tries,
            'updated-at' => $resource->updated_at->toAtomString(),
        ];
    }

}
