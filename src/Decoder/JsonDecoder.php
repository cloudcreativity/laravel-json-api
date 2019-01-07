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

namespace CloudCreativity\LaravelJsonApi\Decoder;

use CloudCreativity\LaravelJsonApi\Contracts\Decoder\DecoderInterface;
use function CloudCreativity\LaravelJsonApi\json_decode;

/**
 * Class Decoder
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonDecoder implements DecoderInterface
{

    /**
     * @inheritDoc
     */
    public function isJsonApi(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function decode($request): \stdClass
    {
        return json_decode($request->getContent());
    }

    /**
     * @inheritdoc
     */
    public function toArray($request): array
    {
        return $request->json()->all();
    }
}
