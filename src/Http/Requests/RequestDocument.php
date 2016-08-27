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

use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceInterface;
use CloudCreativity\JsonApi\Object\Document;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Class RequestDocument
 * @package CloudCreativity\LaravelJsonApi
 */
class RequestDocument
{

    /**
     * @var DocumentInterface
     */
    private $document;

    /**
     * @param ApiInterface $api
     * @param ServerRequestInterface $request
     * @return RequestDocument
     */
    public static function create(ApiInterface $api, ServerRequestInterface $request)
    {
        $decoder = $api->getCodecMatcher()->getDecoder();
        $document = $decoder->decode((string) $request->getBody());

        if (!is_object($document)) {
            throw new RuntimeException('A decoder that decodes to an object must be used.');
        }

        $document = ($document instanceof DocumentInterface) ? $document : new Document($document);

        return new self($document);
    }

    /**
     * RequestDocument constructor.
     * @param DocumentInterface $document
     */
    public function __construct(DocumentInterface $document)
    {
        $this->document = $document;
    }

    /**
     * @return DocumentInterface
     */
    public function getContent()
    {
        return clone $this->document;
    }

    /**
     * @return ResourceInterface
     */
    public function getResource()
    {
        return $this->document->getResource();
    }

    /**
     * @return RelationshipInterface
     */
    public function getRelationship()
    {
        return $this->document->getRelationship();
    }
}
