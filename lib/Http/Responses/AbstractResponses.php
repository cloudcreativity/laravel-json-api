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

namespace CloudCreativity\JsonApi\Http\Responses;

use CloudCreativity\JsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\Responses\ErrorResponseInterface;
use CloudCreativity\JsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Neomerx\JsonApi\Http\Responses;

/**
 * Class Responses
 *
 * @package CloudCreativity\JsonApi
 */
abstract class AbstractResponses extends Responses
{

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    private $schemas;

    /**
     * @var ErrorRepositoryInterface
     */
    private $errorRepository;

    /**
     * @var CodecMatcherInterface
     */
    private $codecs;

    /**
     * @var EncodingParametersInterface|null
     */
    private $parameters;

    /**
     * @var SupportedExtensionsInterface|null
     */
    private $extensions;

    /**
     * @var string|null
     */
    private $urlPrefix;

    /**
     * AbstractResponses constructor.
     *
     * @param FactoryInterface $factory
     * @param ContainerInterface $schemas
     * @param ErrorRepositoryInterface $errors
     * @param CodecMatcherInterface $codecs
     * @param EncodingParametersInterface|null $parameters
     * @param SupportedExtensionsInterface|null $extensions
     * @param string|null $urlPrefix
     */
    public function __construct(
        FactoryInterface $factory,
        ContainerInterface $schemas,
        ErrorRepositoryInterface $errors,
        CodecMatcherInterface $codecs = null,
        EncodingParametersInterface $parameters = null,
        SupportedExtensionsInterface $extensions = null,
        $urlPrefix = null
    ) {
        $this->factory = $factory;
        $this->schemas = $schemas;
        $this->errorRepository = $errors;
        $this->codecs = $codecs;
        $this->parameters = $parameters;
        $this->extensions = $extensions;
        $this->urlPrefix = $urlPrefix;
    }

    /**
     * @param $statusCode
     * @param array $headers
     * @return mixed
     */
    public function statusCode($statusCode, array $headers = [])
    {
        return $this->getCodeResponse($statusCode, $headers);
    }

    /**
     * @param array $headers
     * @return mixed
     */
    public function noContent(array $headers = [])
    {
        return $this->getCodeResponse(204, $headers);
    }

    /**
     * @param $meta
     * @param int $statusCode
     * @param array $headers
     * @return mixed
     */
    public function meta($meta, $statusCode = 200, array $headers = [])
    {
        return $this->getMetaResponse($meta, $statusCode, $headers);
    }

    /**
     * @param $data
     * @param array $links
     * @param mixed $meta
     * @param int $statusCode
     * @param array $headers
     * @return mixed
     */
    public function content(
        $data,
        array $links = [],
        $meta = null,
        $statusCode = self::HTTP_OK,
        array $headers = []
    ) {
        return $this->getContentResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * @inheritdoc
     */
    public function getContentResponse(
        $data,
        $statusCode = self::HTTP_OK,
        $links = null,
        $meta = null,
        array $headers = []
    ) {
        if ($data instanceof PageInterface) {
            list ($data, $meta, $links) = $this->extractPage($data, $meta, $links);
        }

        return parent::getContentResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * @param $resource
     * @param array $links
     * @param mixed $meta
     * @param array $headers
     * @return mixed
     */
    public function created($resource, array $links = [], $meta = null, array $headers = [])
    {
        return $this->getCreatedResponse($resource, $links, $meta, $headers);
    }

    /**
     * @param $data
     * @param array $links
     * @param mixed $meta
     * @param int $statusCode
     * @param array $headers
     * @return mixed
     */
    public function relationship(
        $data,
        array $links = [],
        $meta = null,
        $statusCode = 200,
        array $headers = []
    ) {
        return $this->getIdentifiersResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * @param array|object $data
     * @param int $statusCode
     * @param $links
     * @param $meta
     * @param array $headers
     * @return mixed
     */
    public function getIdentifiersResponse(
        $data,
        $statusCode = self::HTTP_OK,
        $links = null,
        $meta = null,
        array $headers = []
    ) {
        if ($data instanceof PageInterface) {
            list ($data, $meta, $links) = $this->extractPage($data, $meta, $links);
        }

        return parent::getIdentifiersResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * @param mixed $errors
     * @param int|null $defaultStatusCode
     * @param array $headers
     * @return mixed
     */
    public function error($errors, $defaultStatusCode = null, array $headers = [])
    {
        return $this->errors($errors, $defaultStatusCode, $headers);
    }

    /**
     * @param mixed $errors
     * @param int|null $defaultStatusCode
     * @param array $headers
     * @return mixed
     */
    public function errors($errors, $defaultStatusCode = null, array $headers = [])
    {
        if ($errors instanceof ErrorResponseInterface) {
            return $this->getErrorResponse($errors);
        }

        if (is_string($errors)) {
            $errors = $this->errorRepository->error($errors);
        }

        if (is_array($errors)) {
            $errors = $this->errorRepository->errors($errors);
        }

        return $this->errors(
            $this->factory->createErrorResponse($errors, $defaultStatusCode, $headers)
        );
    }

    /**
     * @param ErrorInterface|ErrorInterface[]|ErrorCollection|ErrorResponseInterface $errors
     * @param int $statusCode
     * @param array $headers
     * @return mixed
     */
    public function getErrorResponse($errors, $statusCode = self::HTTP_BAD_REQUEST, array $headers = [])
    {
        if ($errors instanceof ErrorResponseInterface) {
            $statusCode = $errors->getHttpCode();
            $headers = $errors->getHeaders();
            $errors= $errors->getErrors();
        }

        return parent::getErrorResponse($errors, $statusCode, $headers);
    }

    /**
     * @inheritdoc
     */
    protected function getEncoder()
    {
        if ($this->codecs && $encoder = $this->codecs->getEncoder()) {
            return $encoder;
        }

        return $this->factory->createEncoder(
            $this->getSchemaContainer(),
            new EncoderOptions(0, $this->getUrlPrefix())
        );
    }

    /**
     * @inheritdoc
     */
    protected function getUrlPrefix()
    {
        return $this->urlPrefix;
    }

    /**
     * @inheritdoc
     */
    protected function getEncodingParameters()
    {
        return $this->parameters;
    }

    /**
     * @inheritdoc
     */
    protected function getSchemaContainer()
    {
        return $this->schemas;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedExtensions()
    {
        return $this->extensions;
    }

    /**
     * @inheritdoc
     */
    protected function getMediaType()
    {
        if ($this->codecs && $mediaType = $this->codecs->getEncoderRegisteredMatchedType()) {
            return $mediaType;
        }

        return new MediaType(MediaType::JSON_API_TYPE, MediaType::JSON_API_SUB_TYPE);
    }

    /**
     * @param PageInterface $page
     * @param $meta
     * @param $links
     * @return array
     */
    private function extractPage(PageInterface $page, $meta, $links)
    {
        return [
            $page->getData(),
            $this->mergePageMeta($meta, $page),
            $this->mergePageLinks($links, $page),
        ];
    }

    /**
     * @param object|array|null $existing
     * @param PageInterface $page
     * @return array
     */
    private function mergePageMeta($existing, PageInterface $page)
    {
        if (!$merge = $page->getMeta()) {
            return $existing;
        }

        $existing = (array) $existing ?: [];

        if ($key = $page->getMetaKey()) {
            $existing[$key] = $merge;
            return $existing;
        }

        return array_replace($existing, (array) $merge);
    }

    /**
     * @param array $existing
     * @param PageInterface $page
     * @return array
     */
    private function mergePageLinks(array $existing, PageInterface $page)
    {
        return array_replace($existing, array_filter([
            DocumentInterface::KEYWORD_FIRST => $page->getFirstLink(),
            DocumentInterface::KEYWORD_PREV => $page->getPreviousLink(),
            DocumentInterface::KEYWORD_NEXT => $page->getNextLink(),
            DocumentInterface::KEYWORD_LAST => $page->getLastLink(),
        ]));
    }

}
