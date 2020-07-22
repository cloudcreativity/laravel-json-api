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

namespace DummyApp\JsonApi\QueueJobs;

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
        return [
            'attempts' => $resource->attempts,
            'completedAt' => $resource->completed_at,
            'createdAt' => $resource->created_at,
            'failed' => $resource->failed,
            'resourceType' => $resource->resource_type,
            'timeout' => $resource->timeout,
            'timeoutAt' => $resource->timeout_at,
            'tries' => $resource->tries,
            'updatedAt' => $resource->updated_at,
        ];
    }

}
