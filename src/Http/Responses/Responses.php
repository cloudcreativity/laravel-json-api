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

namespace CloudCreativity\LaravelJsonApi\Http\Responses;

use CloudCreativity\JsonApi\Http\Responses\AbstractResponses;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;

/**
 * Class Responses
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Responses extends AbstractResponses
{

    /**
     * Statically create the responses.
     *
     * If there is an API handling the inbound request, this method will return that API.
     * If not, then it will use the provided API name, or the default API if no name is provided.
     *
     * @param string|null $apiName
     * @return Responses
     */
    public static function create($apiName = null)
    {
        /** @var JsonApiService $service */
        $service = app('json-api.service');
        $api = $service->requestApi() ?: $service->retrieve($apiName);
        $request = $service->request();

        return $api->createResponse($request ? $request->getParameters() : null);
    }

    /**
     * @inheritdoc
     */
    protected function createResponse($content, $statusCode, array $headers)
    {
        return response($content, $statusCode, $headers);
    }

}
