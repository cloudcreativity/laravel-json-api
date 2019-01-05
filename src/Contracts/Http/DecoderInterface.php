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

namespace CloudCreativity\LaravelJsonApi\Contracts\Http;

use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Interface DecoderInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface DecoderInterface
{

    /**
     * @return MediaTypeInterface
     */
    public function getMediaType(): MediaTypeInterface;

    /**
     * Decode a JSON API document from a request.
     *
     * @param Request $request
     * @return \stdClass|null
     *      the JSON API document, or null if the request is not providing JSON API content.
     * @throws HttpException
     * @throws JsonApiException
     */
    public function decode($request): ?\stdClass;

    /**
     * Extract content data from a request.
     *
     * @param Request $request
     * @return array
     * @throws HttpException
     * @throws JsonApiException
     */
    public function extract($request): array;
}
