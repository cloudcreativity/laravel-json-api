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

use CloudCreativity\JsonApi\Contracts\Error\ErrorObjectInterface;
use CloudCreativity\JsonApi\Error\ErrorException;
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
     * @throws ErrorException
     */
    public function missingMethod($parameters = [])
    {
        $this->methodNotAllowed();
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return void
     * @throws ErrorException
     */
    public function __call($method, $parameters)
    {
        $this->notImplemented();
    }

    /**
     * Helper method to throw a not found exception.
     *
     * @throws ErrorException
     */
    public function notFound()
    {
        throw new ErrorException([
            ErrorObjectInterface::TITLE => 'Not Found',
            ErrorObjectInterface::STATUS => Response::HTTP_NOT_FOUND,
        ]);
    }

    /**
     * Helper method to throw a not implemented exception.
     *
     * @throws ErrorException
     */
    public function notImplemented()
    {
        throw new ErrorException([
            ErrorObjectInterface::TITLE => 'Not Implemented',
            ErrorObjectInterface::STATUS => Response::HTTP_NOT_IMPLEMENTED,
        ]);
    }

    /**
     * Helper method to throw a method not allowed exception.
     *
     * @throws ErrorException
     */
    public function methodNotAllowed()
    {
        throw new ErrorException([
            ErrorObjectInterface::TITLE => 'Method Not Allowed',
            ErrorObjectInterface::STATUS => Response::HTTP_METHOD_NOT_ALLOWED,
        ]);
    }
}
