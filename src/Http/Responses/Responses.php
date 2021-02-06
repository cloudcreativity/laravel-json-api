<?php

/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Responses;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Codec\Codec;
use CloudCreativity\LaravelJsonApi\Codec\Encoding;
use CloudCreativity\LaravelJsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Document\Error\Error;
use CloudCreativity\LaravelJsonApi\Encoder\Neomerx\Factory;
use CloudCreativity\LaravelJsonApi\Routing\Route;
use CloudCreativity\LaravelJsonApi\Utils\Helpers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Responses as BaseResponses;
use UnexpectedValueException;

/**
 * Class Responses
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Responses extends BaseResponses
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var Route
     */
    private $route;

    /**
     * @var ExceptionParserInterface
     */
    private $exceptions;

    /**
     * @var Codec|null
     */
    private $codec;

    /**
     * @var EncodingParametersInterface|null
     */
    private $parameters;

    /**
     * Responses constructor.
     *
     * @param Factory $factory
     * @param Api $api
     *      the API that is sending the responses.
     * @param Route $route
     * @param $exceptions
     */
    public function __construct(
        Factory $factory,
        Api $api,
        Route $route,
        ExceptionParserInterface $exceptions
    ) {
        $this->factory = $factory;
        $this->api = $api;
        $this->route = $route;
        $this->exceptions = $exceptions;
    }

    /**
     * @param Codec $codec
     * @return Responses
     */
    public function withCodec(Codec $codec): self
    {
        $this->codec = $codec;

        return $this;
    }

    /**
     * Send a response with the supplied media type content.
     *
     * @param string $mediaType
     * @return $this
     */
    public function withMediaType(string $mediaType): self
    {
        if (!$encoding = $this->api->getEncodings()->find($mediaType)) {
            throw new InvalidArgumentException(
                "Media type {$mediaType} is not valid for API {$this->api->getName()}."
            );
        }

        $codec = $this->factory->createCodec(
            $this->api->getContainer(),
            $encoding,
            null
        );

        return $this->withCodec($codec);
    }

    /**
     * Set the encoding options.
     *
     * @param int $options
     * @param int $depth
     * @param string|null $mediaType
     * @return $this
     */
    public function withEncoding(
        int $options = 0,
        int $depth = 512,
        string $mediaType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ): self {
        $encoding = Encoding::create(
            $mediaType,
            $options,
            $this->api->getUrl()->toString(),
            $depth
        );

        $codec = $this->factory->createCodec(
            $this->api->getContainer(),
            $encoding,
            null
        );

        return $this->withCodec($codec);
    }

    /**
     * Set the encoding parameters to use.
     *
     * @param EncodingParametersInterface|null $parameters
     * @return $this
     */
    public function withEncodingParameters(?EncodingParametersInterface $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param $statusCode
     * @param array $headers
     * @return Response
     */
    public function statusCode($statusCode, array $headers = []): Response
    {
        return $this->getCodeResponse($statusCode, $headers);
    }

    /**
     * @param array $headers
     * @return Response
     */
    public function noContent(array $headers = []): Response
    {
        return $this->getCodeResponse(204, $headers);
    }

    /**
     * @param $meta
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function meta($meta, $statusCode = self::HTTP_OK, array $headers = []): Response
    {
        return $this->getMetaResponse($meta, $statusCode, $headers);
    }

    /**
     * @param array $links
     * @param $meta
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function noData(array $links = [], $meta = null, $statusCode = self::HTTP_OK, array $headers = []): Response
    {
        $encoder = $this->getEncoder();
        $content = $encoder->withLinks($links)->encodeMeta($meta ?: []);

        return $this->createJsonApiResponse($content, $statusCode, $headers, true);
    }

    /**
     * @param $data
     * @param array $links
     * @param mixed $meta
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function content(
        $data,
        array $links = [],
        $meta = null,
        $statusCode = self::HTTP_OK,
        array $headers = []
    ): Response {
        return $this->getContentResponse($data, $statusCode, $links, $meta, $headers);
    }


    /**
     * Get response with regular JSON API Document in body.
     *
     * @param array|object $data
     * @param int $statusCode
     * @param null $links
     * @param null $meta
     * @param array $headers
     * @return Response
     */
    public function getContentResponse(
        $data,
        $statusCode = self::HTTP_OK,
        $links = null,
        $meta = null,
        array $headers = []
    ): Response {
        if ($data instanceof PageInterface) {
            [$data, $meta, $links] = $this->extractPage($data, $meta, $links);
        }

        return parent::getContentResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * @param $resource
     * @param array $links
     * @param mixed $meta
     * @param array $headers
     * @return Response
     */
    public function created($resource = null, array $links = [], $meta = null, array $headers = []): Response
    {
        if ($this->isNoContent($resource, $links, $meta)) {
            return $this->noContent();
        }

        if (is_null($resource)) {
            return $this->noData($links, $meta, self::HTTP_OK, $headers);
        }

        if ($this->isAsync($resource)) {
            return $this->accepted($resource, $links, $meta, $headers);
        }

        return $this->getCreatedResponse($resource, $links, $meta, $headers);
    }

    /**
     * Return a response for a resource update request.
     *
     * @param $resource
     * @param array $links
     * @param mixed $meta
     * @param array $headers
     * @return Response
     */
    public function updated(
        $resource = null,
        array $links = [],
        $meta = null,
        array $headers = []
    ): Response {
        return $this->getResourceResponse($resource, $links, $meta, $headers);
    }

    /**
     * Return a response for a resource delete request.
     *
     * @param mixed|null $resource
     * @param array $links
     * @param mixed|null $meta
     * @param array $headers
     * @return Response
     */
    public function deleted(
        $resource = null,
        array $links = [],
        $meta = null,
        array $headers = []
    ): Response {
        return $this->getResourceResponse($resource, $links, $meta, $headers);
    }

    /**
     * @param AsynchronousProcess $job
     * @param array $links
     * @param null $meta
     * @param array $headers
     * @return Response
     */
    public function accepted(AsynchronousProcess $job, array $links = [], $meta = null, array $headers = []): Response
    {
        $headers['Content-Location'] = $this->getResourceLocationUrl($job);

        return $this->getContentResponse($job, Response::HTTP_ACCEPTED, $links, $meta, $headers);
    }

    /**
     * @param AsynchronousProcess $job
     * @param array $links
     * @param null $meta
     * @param array $headers
     * @return RedirectResponse|mixed
     */
    public function process(AsynchronousProcess $job, array $links = [], $meta = null, array $headers = [])
    {
        if (!$job->isPending() && $location = $job->getLocation()) {
            $headers['Location'] = $location;
            return $this->createJsonApiResponse(null, Response::HTTP_SEE_OTHER, $headers);
        }

        return $this->getContentResponse($job, self::HTTP_OK, $links, $meta, $headers);
    }

    /**
     * @param $data
     * @param array $links
     * @param mixed $meta
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function relationship(
        $data,
        array $links = [],
        $meta = null,
        $statusCode = 200,
        array $headers = []
    ): Response {
        return $this->getIdentifiersResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * @param array|object $data
     * @param int $statusCode
     * @param $links
     * @param $meta
     * @param array $headers
     * @return Response
     */
    public function getIdentifiersResponse(
        $data,
        $statusCode = self::HTTP_OK,
        $links = null,
        $meta = null,
        array $headers = []
    ): Response {
        if ($data instanceof PageInterface) {
            [$data, $meta, $links] = $this->extractPage($data, $meta, $links);
        }

        return parent::getIdentifiersResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * Create a response containing a single error.
     *
     * @param Error|ErrorInterface|array $error
     * @param int|null $defaultStatusCode
     * @param array $headers
     * @return Response
     */
    public function error($error, int $defaultStatusCode = null, array $headers = []): Response
    {
        if (!$error instanceof ErrorInterface) {
            $error = $this->factory->createError(
                Error::cast($error)
            );
        }

        if (!$error instanceof ErrorInterface) {
            throw new UnexpectedValueException('Expecting an error object or array.');
        }

        return $this->errors([$error], $defaultStatusCode, $headers);
    }

    /**
     * Create a response containing multiple errors.
     *
     * @param iterable $errors
     * @param int|null $defaultStatusCode
     * @param array $headers
     *
     * @return Response
     */
    public function errors(iterable $errors, int $defaultStatusCode = null, array $headers = []): Response
    {
        $errors = $this->factory->createErrors($errors);
        $statusCode = Helpers::httpErrorStatus($errors, $defaultStatusCode);

        return $this->getErrorResponse($errors, $statusCode, $headers);
    }

    /**
     * @param $resource
     * @param array $links
     * @param null $meta
     * @param array $headers
     * @return Response
     */
    protected function getResourceResponse($resource, array $links = [], $meta = null, array $headers = []): Response
    {
        if ($this->isNoContent($resource, $links, $meta)) {
            return $this->noContent();
        }

        if (is_null($resource)) {
            return $this->noData($links, $meta, self::HTTP_OK, $headers);
        }

        if ($this->isAsync($resource)) {
            return $this->accepted($resource, $links, $meta, $headers);
        }

        return $this->getContentResponse($resource, self::HTTP_OK, $links, $meta, $headers);
    }

    /**
     * @inheritdoc
     */
    protected function getEncoder()
    {
        return $this->getCodec()->getEncoder();
    }

    /**
     * @inheritdoc
     */
    protected function getMediaType()
    {
        return $this->getCodec()->getEncodingMediaType();
    }

    /**
     * @return Codec
     */
    protected function getCodec()
    {
        if (!$this->codec) {
            $this->codec = $this->getDefaultCodec();
        }

        return $this->codec;
    }

    /**
     * @return Codec
     */
    protected function getDefaultCodec()
    {
        if ($this->route->hasCodec()) {
            return $this->route->getCodec();
        }

        return $this->api->getDefaultCodec();
    }

    /**
     * @inheritdoc
     */
    protected function getUrlPrefix()
    {
        return $this->api->getUrl()->toString();
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
        return $this->api->getContainer();
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedExtensions()
    {
        return $this->api->getSupportedExtensions();
    }

    /**
     * Create HTTP response.
     *
     * @param string|null $content
     * @param int $statusCode
     * @param array $headers
     *
     * @return Response
     */
    protected function createResponse($content, $statusCode, array $headers): Response
    {
        return response($content, $statusCode, $headers);
    }

    /**
     * Does a no content response need to be returned?
     *
     * @param $resource
     * @param $links
     * @param $meta
     * @return bool
     */
    protected function isNoContent($resource, $links, $meta)
    {
        return is_null($resource) && empty($links) && empty($meta);
    }

    /**
     * Does the data represent an asynchronous process?
     *
     * @param $data
     * @return bool
     */
    protected function isAsync($data)
    {
        return $data instanceof AsynchronousProcess;
    }

    /**
     * Reset the encoder.
     *
     * @return void
     */
    protected function resetEncoder()
    {
        $this->getEncoder()->withLinks([])->withMeta(null);
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
