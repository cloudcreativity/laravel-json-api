<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Broadcasting;

use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use CloudCreativity\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

/**
 * Trait BroadcastsData
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait BroadcastsData
{

    /**
     * @return string
     */
    protected function broadcastApi()
    {
        if (property_exists($this, 'broadcastApi')) {
            return $this->broadcastApi;
        }

        return 'default';
    }

    /**
     * @return string|null
     */
    protected function broadcastApiHost()
    {
        if (property_exists($this, 'broadcastApiHost')) {
            return $this->broadcastApiHost;
        }

        return null;
    }

    /**
     * @return Encoder
     */
    protected function broadcastEncoder()
    {
        /** @var JsonApiService $service */
        $service = app(JsonApiService::class);

        return $service->encoder($this->broadcastApi(), $this->broadcastApiHost());
    }

    /**
     * @param $data
     * @param string|string[]|null $includePaths
     * @param array|null $fieldsets
     * @return array
     */
    protected function serializeData($data, $includePaths = null, array $fieldsets = null)
    {
        $params = new EncodingParameters($includePaths ? (array) $includePaths : null, $fieldsets);

        return $this->broadcastEncoder()->serializeData($data, $params);
    }
}
