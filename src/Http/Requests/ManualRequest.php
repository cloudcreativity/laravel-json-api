<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use Neomerx\JsonApi\Http\Request;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * Class ManualRequest
 * @package CloudCreativity\LaravelJsonApi
 */
final class ManualRequest extends Request
{

    /**
     * ManualRequest constructor.
     * @param string $method
     * @param array $headers
     * @param array $params
     */
    public function __construct($method = 'GET', array $headers = [], array $params = [])
    {
        $headers = new HeaderBag($headers);

        $methodClosure = function () use ($method) {
            return $method;
        };

        $headerClosure = function ($name) use ($headers) {
            return $headers->get($name);
        };

        $queryParamsClosure = function () use ($params) {
            return $params;
        };

        parent::__construct($methodClosure, $headerClosure, $queryParamsClosure);
    }
}
