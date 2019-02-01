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

namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Codec\Decoding;
use CloudCreativity\LaravelJsonApi\Codec\DecodingList;
use CloudCreativity\LaravelJsonApi\Codec\EncodingList;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;
use DummyApp\Avatar;
use DummyApp\JsonApi\FileDecoder;

class ContentNegotiator extends BaseContentNegotiator
{

    /**
     * @param Avatar|null $avatar
     * @return EncodingList
     */
    protected function encodingsForOne(?Avatar $avatar): EncodingList
    {
        $mediaType = optional($avatar)->media_type;

        return $this
            ->encodingMediaTypes()
            ->when($this->request->isMethod('GET'), $mediaType);
    }

    /**
     * @param Avatar|null $avatar
     * @return DecodingList
     */
    protected function decodingsForResource(?Avatar $avatar): DecodingList
    {
        return $this
            ->decodingMediaTypes()
            ->when(is_null($avatar), Decoding::create('multipart/form-data', new FileDecoder()))
            ->when(is_null($avatar), Decoding::create('multipart/form-data; boundary=*', new FileDecoder()));
    }

}
