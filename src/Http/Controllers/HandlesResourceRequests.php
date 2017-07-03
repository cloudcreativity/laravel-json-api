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

use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;

/**
 * Class ReplyTrait
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait HandlesResourceRequests
{

    /**
     * @return JsonApiService
     */
    protected function jsonApi()
    {
        return app('json-api.service');
    }

    /**
     * Get the API instance.
     *
     * If there is an API handling the inbound request, this method will return that API.
     * If not, then it will return the default API or the API that specified on the `$api` property of the
     * implementing class.
     *
     * @return Api
     */
    protected function api()
    {
        if ($api = $this->jsonApi()->requestApi()) {
            return $api;
        }

        $name = property_exists($this, 'api') ? $this->api : null;

        return $this->jsonApi()->retrieve($name);
    }

    /**
     * Get the store for the current API.
     *
     * @return StoreInterface
     */
    protected function store()
    {
        return $this->api()->getStore();
    }

    /**
     * @return Responses
     */
    protected function reply()
    {
        $request = $this->jsonApi()->request();

        return $this->api()->createResponse(
            $request ? $request->getParameters() : null
        );
    }
}
