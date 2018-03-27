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
     * If no API name is provided, the API handling the inbound HTTP request will be used.
     *
     * @param string|null $apiName
     * @return Responses
     */
    public static function create($apiName = null)
    {
        $api = json_api($apiName);
        $request = json_api_request();

        return $api->response($request ? $request->getParameters() : null);
    }

    /**
     * @inheritdoc
     */
    public function getErrorResponse($errors, $statusCode = self::HTTP_BAD_REQUEST, array $headers = [])
    {
        /** If the error occurred while we were encoding, the encoder needs to be reset. */
        $this->resetEncoder();

        return parent::getErrorResponse($errors, $statusCode, $headers);
    }

    /**
     * @inheritdoc
     */
    protected function createResponse($content, $statusCode, array $headers)
    {
        return response($content, $statusCode, $headers);
    }

    /**
     * Reset the encoder.
     *
     * @return void
     */
    protected function resetEncoder()
    {
        $this->getEncoder()->withLinks([])->withMeta(null);
    }

}
