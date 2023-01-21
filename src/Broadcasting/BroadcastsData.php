<?php

/*
 * Copyright 2022 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\LaravelJsonApi\Http\Query\QueryParameters;

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
        return property_exists($this, 'broadcastApi') ? $this->broadcastApi : null;
    }

    /**
     * @return SerializerInterface
     */
    protected function broadcastEncoder()
    {
        return json_api($this->broadcastApi())->encoder();
    }

    /**
     * @param $data
     * @param string|string[]|null $includePaths
     * @param array|null $fieldsets
     * @return array
     */
    protected function serializeData($data, $includePaths = null, array $fieldsets = null)
    {
        $params = new QueryParameters($includePaths ? (array) $includePaths : null, $fieldsets);

        return $this->broadcastEncoder()->serializeData($data, $params);
    }
}
