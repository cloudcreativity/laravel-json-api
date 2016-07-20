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

namespace CloudCreativity\LaravelJsonApi\Contracts\Http\Requests;

use CloudCreativity\JsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RequestException;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Interface RequestHandlerInterface
 * @package CloudCreativity\LaravelJsonApi
 */
interface RequestHandlerInterface extends ValidatesWhenResolved
{

    /**
     * The resource type that this request handles.
     *
     * @return string
     */
    public function getResourceType();

    /**
     * Get the record that the request relates to.
     *
     * E.g. if the request is `GET /posts/1`, then the record is the object that the
     * store resolves as being `post` with id 1.
     *
     * @return object
     * @throws RequestException
     *      if the request does not related to a specific record.
     */
    public function getRecord();

    /**
     * Get the request body content as a JSON API document.
     *
     * @return DocumentInterface
     */
    public function getDocument();

    /**
     * Get the encoding parameters that the client sent.
     *
     * @return EncodingParametersInterface
     */
    public function getEncodingParameters();

    /**
     * Get the underlying HTTP request.
     *
     * @return Request
     */
    public function getHttpRequest();

    /**
     * Did validation complete successfully?
     *
     * @return bool
     */
    public function isValid();

}
