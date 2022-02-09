<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Contracts\Decoder;

use Illuminate\Http\Request;
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
     * Return request data as an array.
     *
     * Request data returned from this method is used for validation. If it is
     * JSON API data, it will also be the data passed to adapters.
     *
     * If the decoder is unable to return an array when extracting request data,
     * it MUST throw a HTTP exception or a JSON API exception.
     *
     * @param Request $request
     * @return array
     * @throws HttpException
     * @throws JsonApiException
     */
    public function decode($request): array;
}
