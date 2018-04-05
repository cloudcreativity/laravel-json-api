<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Http\Middleware;

use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Http\Message\ServerRequestInterface;
use function CloudCreativity\JsonApi\http_contains_body;

/**
 * Trait NegotiatesContent
 *
 * @package CloudCreativity\JsonApi
 */
trait NegotiatesContent
{

    /**
     * Perform content negotiation.
     *
     * @param HttpFactoryInterface $httpFactory
     * @param ServerRequestInterface $request
     * @param CodecMatcherInterface $codecMatcher
     * @throws JsonApiException
     * @see http://jsonapi.org/format/#content-negotiation
     */
    protected function doContentNegotiation(
        HttpFactoryInterface $httpFactory,
        ServerRequestInterface $request,
        CodecMatcherInterface $codecMatcher
    ) {
        $parser = $httpFactory->createHeaderParametersParser();
        $checker = $httpFactory->createHeadersChecker($codecMatcher);

        $checker->checkHeaders($parser->parse($request, http_contains_body($request)));
    }
}
