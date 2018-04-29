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

namespace CloudCreativity\LaravelJsonApi\Schema;

use CloudCreativity\LaravelJsonApi\Api\LinkGenerator;

/**
 * Trait CreatesLinks
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated use the `json_api` helper function instead.
 */
trait CreatesLinks
{

    /**
     * Get the links generator.
     *
     * The links generator makes it easy to create JSON API links objects. Links generators are scoped
     * to a specific API, which defaults to the API handling the inbound HTTP request. If you use
     * this helper method outside of a HTTP request (e.g. queued broadcasting), you must specify the
     * API to use on the `api` property of the implementing class.
     *
     * @return LinkGenerator
     */
    protected function links()
    {
        return json_api($this->apiName())->links();
    }

    /**
     * Get the API name.
     *
     * @return string|null
     */
    protected function apiName()
    {
        return property_exists($this, 'api') ? $this->api : null;
    }
}
