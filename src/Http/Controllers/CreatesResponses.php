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

namespace CloudCreativity\LaravelJsonApi\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;

/**
 * Trait CreatesResponses
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait CreatesResponses
{

    /**
     * Get the API instance.
     *
     * This will return the API handling the inbound HTTP request. If you are using this
     * trait in a context where there is no API handling the inbound HTTP request, you
     * can specify the API to use by setting the `api` property on the implementing class.
     *
     * @return Api
     */
    protected function api()
    {
        return json_api($this->apiName());
    }

    /**
     * Get the responses factory.
     *
     * This will return the resposnes factory for the API handling the inbound HTTP request.
     * If you are using this trait in a context where there is no API handling the inbound
     * HTTP request, you can specify the API to use by setting the `api` property on
     * the implementing class.
     *
     * @return Responses
     */
    protected function reply()
    {
        return response()->jsonApi($this->apiName());
    }

    /**
     * @return string|null
     */
    protected function apiName()
    {
        return property_exists($this, 'api') ? $this->api : null;
    }
}
