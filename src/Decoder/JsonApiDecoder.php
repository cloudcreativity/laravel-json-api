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

namespace CloudCreativity\LaravelJsonApi\Decoder;

use CloudCreativity\LaravelJsonApi\Contracts\Decoder\DecoderInterface;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use function CloudCreativity\LaravelJsonApi\json_decode;

/**
 * Class JsonApiDecoder
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiDecoder implements DecoderInterface
{

    /**
     * Decode a JSON API document from a request.
     *
     * JSON API request content MUST be decoded as an object, as it is not possible to validate
     * that the request content complies with the JSON API spec if it is JSON decoded to an
     * associative array.
     *
     * If the decoder is unable to return an object when decoding content, it MUST throw
     * a HTTP exception or a JSON API exception.
     *
     * @param Request $request
     * @return \stdClass
     *      the JSON API document.
     * @throws JsonApiException
     * @throws \LogicException
     *      if the decoder does not decode JSON API content.
     */
    public function document($request): \stdClass
    {
        return json_decode($request->getContent());
    }

    /**
     * @inheritdoc
     */
    public function decode($request): array
    {
        return $request->json()->all();
    }
}
