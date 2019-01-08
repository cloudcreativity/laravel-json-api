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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\ContentNegotiation;

use CloudCreativity\LaravelJsonApi\Codec\Decoding;
use CloudCreativity\LaravelJsonApi\Codec\DecodingList;
use CloudCreativity\LaravelJsonApi\Codec\Encoding;
use CloudCreativity\LaravelJsonApi\Codec\EncodingList;
use CloudCreativity\LaravelJsonApi\Decoder\JsonDecoder;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator;

class TestContentNegotiator extends ContentNegotiator
{

    /**
     * @return EncodingList
     */
    protected function supportedEncodings(): EncodingList
    {
        return parent::supportedEncodings()->push(
            Encoding::create('application/json')
        );
    }

    /**
     * @return DecodingList
     */
    protected function supportedDecodings(): DecodingList
    {
        return parent::supportedDecodings()->push(
            Decoding::create('application/json', new JsonDecoder())
        );
    }
}
