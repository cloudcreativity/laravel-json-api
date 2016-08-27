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
use CloudCreativity\JsonApi\Contracts\Http\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorFactoryInterface;
use CloudCreativity\JsonApi\Exceptions\ValidationException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class RequestInitiator
 * @package CloudCreativity\LaravelJsonApi
 */
class RequestInitiator
{

    /**
     * @var HttpFactoryInterface
     */
    private $factory;

    /**
     * @var RequestInterpreterInterface
     */
    private $interpreter;

    /**
     * @var ValidatorFactoryInterface
     */
    private $validatorFactory;

    /**
     * RequestInitiator constructor.
     * @param HttpFactoryInterface $factory
     * @param RequestInterpreterInterface $interpreter
     * @param ValidatorFactoryInterface $validatorFactory
     */
    public function __construct(
        HttpFactoryInterface $factory,
        RequestInterpreterInterface $interpreter,
        ValidatorFactoryInterface $validatorFactory
    ) {
        $this->factory = $factory;
        $this->interpreter = $interpreter;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @param ApiInterface $api
     * @param ServerRequestInterface $request
     * @throws JsonApiException
     */
    public function doContentNegotiation(ApiInterface $api, ServerRequestInterface $request)
    {
        $parser = $this->factory->createHeaderParametersParser();
        $checker = $this->factory->createHeadersChecker($api->getCodecMatcher());

        $checker->checkHeaders($parser->parse($request));
    }

    /**
     * @param ServerRequestInterface $request
     * @return EncodingParametersInterface
     * @throws JsonApiException
     */
    public function parseParameters(ServerRequestInterface $request)
    {
        return $this
            ->factory
            ->createQueryParametersParser()
            ->parse($request);
    }

    /**
     * @param ApiInterface $api
     * @param ServerRequestInterface $request
     * @return RequestDocument|null
     * @throws JsonApiException
     */
    public function parseDocument(ApiInterface $api, ServerRequestInterface $request)
    {
        if (!$this->interpreter->isExpectingDocument()) {
            return null;
        }

        $document = RequestDocument::create($api, $request);
        $validator = $this->documentValidator();

        if (!$validator->isValid($document->getContent())) {
            throw new ValidationException($validator->getErrors());
        }

        return $document;
    }

    /**
     * @return DocumentValidatorInterface
     */
    private function documentValidator()
    {
        if (!$this->interpreter->isModifyRelationship()) {
            return $this->validatorFactory->resourceDocument();
        } else {
            return $this->validatorFactory->relationshipDocument();
        }
    }
}
