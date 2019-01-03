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

namespace CloudCreativity\LaravelJsonApi\Queue;

use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;

trait AsyncSchema
{

    /**
     * @return string
     */
    public function getResourceType()
    {
        $api = property_exists($this, 'api') ? $this->api : null;

        return json_api($api)->getJobs()->getResource();
    }

    /**
     * @param AsynchronousProcess|null $resource
     * @return string
     */
    public function getSelfSubUrl($resource = null)
    {
        if (!$resource) {
            return '/' . $this->getResourceType();
        }

        return sprintf(
            '/%s/%s/%s',
            $resource->getResourceType(),
            $this->getResourceType(),
            $this->getId($resource)
        );
    }
}
