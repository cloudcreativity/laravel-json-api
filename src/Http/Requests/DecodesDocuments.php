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

use CloudCreativity\JsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\JsonApi\Object\Document;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Http\Request as HttpRequest;
use RuntimeException;

/**
 * Class DecodesDocuments
 * @package CloudCreativity\LaravelJsonApi
 */
trait DecodesDocuments
{

    /**
     * @param HttpRequest $request
     * @return DocumentInterface|null
     */
    protected function decodeDocument(HttpRequest $request)
    {
        /** @var JsonApiService $service */
        $service = app(JsonApiService::class);

        $object = $service
            ->getApi()
            ->getCodecMatcher()
            ->getDecoder()
            ->decode($request->getContent());

        if (!is_object($object)) {
            throw new RuntimeException('You must use a decoder that returns an object.');
        }

        return ($object instanceof DocumentInterface) ? $object : new Document($object);
    }
}
