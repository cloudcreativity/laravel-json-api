<?php

/**
 * Copyright 2015 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Http\Controllers;

use CloudCreativity\JsonApi\Error\ThrowableError;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Class JsonApiController
 * @package CloudCreativity\JsonApi\Laravel
 */
class JsonApiController extends Controller
{

    use QueryCheckerTrait,
        DocumentValidatorTrait,
        ReplyTrait;

    /**
     * Whether query parameters should automatically be checked before the controller action method is invoked.
     *
     * @var bool
     */
    protected $autoCheckQueryParameters = true;

    /**
     * @param string $method
     * @param array $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        if (true === $this->autoCheckQueryParameters) {
            $this->checkParameters();
        }

        return parent::callAction($method, $parameters);
    }

    /**
     * @param array $parameters
     * @return void
     * @throws ThrowableError
     */
    public function missingMethod($parameters = [])
    {
        throw new ThrowableError('Method Not Allowed', Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return void
     * @throws ThrowableError
     */
    public function __call($method, $parameters)
    {
        throw new ThrowableError('Not Implemented', Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * Helper method to throw a not found exception.
     *
     * @return void
     * @throws ThrowableError
     */
    public function notFound()
    {
        throw new ThrowableError('Not Found', Response::HTTP_NOT_FOUND);
    }
}
