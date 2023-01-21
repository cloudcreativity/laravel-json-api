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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Http\Responses;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Codec\Codec;
use CloudCreativity\LaravelJsonApi\Codec\Encoding;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Document\Error\Error;
use CloudCreativity\LaravelJsonApi\Encoder\Encoder;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Routing\Route;
use CloudCreativity\LaravelJsonApi\Utils\Helpers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use Neomerx\JsonApi\Http\BaseResponses;
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
    private Factory $factory;

    /**
     * @var Api
     */
    private Api $api;

    /**
     * @var Route
     */
    private Route $route;

    /**
     * @var ExceptionParserInterface
     */
    private ExceptionParserInterface $exceptions;

    /**
     * @var Codec|null
     */
    private ?Codec $codec = null;

    /**
     * @var QueryParametersInterface|null
     */
    private ?QueryParametersInterface $parameters = null;

    /**
     * @var EncoderInterface|null
     */
    private ?EncoderInterface $encoder = null;

    /**
     * Responses constructor.
     *
     * @param Factory $factory
     * @param Api $api
     *      the API that is sending the responses.
     * @param Route $route
     * @param ExceptionParserInterface $exceptions
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
     * @param QueryParametersInterface|null $parameters
     * @return $this
     */
    public function withEncodingParameters(?QueryParametersInterface $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function statusCode(int $statusCode, array $headers = []): Response
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
     * @param mixed $meta
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function meta($meta, int $statusCode = self::HTTP_OK, array $headers = []): Response
    {
        return $this->getMetaResponse($meta, $statusCode, $headers);
    }

    /**
     * @param array $links
     * @param mixed $meta
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
        int $statusCode = self::HTTP_OK,
        array $headers = []
    ): Response {
        return $this->getContentResponseBackwardsCompat($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * Get response with regular JSON:API Document in body.
     *
     * This method provides backwards compatibility with the `getContentResponse()` method
     * from the Neomerx 1.x package.
     *
     * @param array|object $data
     * @param int $statusCode
     * @param array|null $links
     * @param mixed|null $meta
     * @param array $headers
     * @return Response
     */
    public function getContentResponseBackwardsCompat(
        $data,
        int $statusCode = self::HTTP_OK,
        array $links = null,
        $meta = null,
        array $headers = []
    ): Response
    {
        if ($data instanceof PageInterface) {
            [$data, $meta, $links] = $this->extractPage($data, $meta, $links);
        }

        $this->getEncoder()->withLinks($links ?? [])->withMeta($meta);

        return parent::getContentResponse($data, $statusCode, $headers);
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

        return $this->getCreatedResponseBackwardsCompat($resource, $links, $meta, $headers);
    }

    /**
     * @param $resource
     * @param array $links
     * @param null $meta
     * @param array $headers
     * @return Response
     */
    public function getCreatedResponseBackwardsCompat(
        $resource,
        array $links = [],
        $meta = null,
        array $headers = []
    ): Response
    {
        $this->getEncoder()->withLinks($links)->withMeta($meta);

        $url = $this
            ->getResourceSelfLink($resource)
            ->getStringRepresentation($this->getUrlPrefix());

        return $this->getCreatedResponse($resource, $url, $headers);
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
        $url = $this
            ->getResourceSelfLink($job)
            ->getStringRepresentation($this->getUrlPrefix());

        $headers['Content-Location'] = $url;

        return $this->getContentResponseBackwardsCompat($job, Response::HTTP_ACCEPTED, $links, $meta, $headers);
    }

    /**
     * @param AsynchronousProcess $job
     * @param array $links
     * @param mixed|null $meta
     * @param array $headers
     * @return RedirectResponse|mixed
     */
    public function process(AsynchronousProcess $job, array $links = [], $meta = null, array $headers = [])
    {
        if (!$job->isPending() && $location = $job->getLocation()) {
            $headers['Location'] = $location;
            return $this->createJsonApiResponse(null, Response::HTTP_SEE_OTHER, $headers);
        }

        return $this->getContentResponseBackwardsCompat($job, self::HTTP_OK, $links, $meta, $headers);
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
        return $this->getIdentifiersResponseBackwardsCompat($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * @param array|object $data
     * @param int $statusCode
     * @param array|null $links
     * @param mixed|null $meta
     * @param array $headers
     * @return Response
     */
    public function getIdentifiersResponseBackwardsCompat(
        $data,
        int $statusCode = self::HTTP_OK,
        array $links = null,
        $meta = null,
        array $headers = []
    ): Response {
        if ($data instanceof PageInterface) {
            [$data, $meta, $links] = $this->extractPage($data, $meta, $links);
        }

        $this->getEncoder()->withLinks($links)->withMeta($meta);

        return parent::getIdentifiersResponse($data, $statusCode, $headers);
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
            $error = $this->factory->createDocumentMapper()->createError(
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
        $errors = $this->factory->createDocumentMapper()->createErrors($errors);
        $statusCode = Helpers::httpErrorStatus($errors, $defaultStatusCode);

        return $this->getErrorResponse($errors, $statusCode, $headers);
    }

    /**
     * @param $resource
     * @param array $links
     * @param mixed|null $meta
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

        return $this->getContentResponseBackwardsCompat($resource, self::HTTP_OK, $links, $meta, $headers);
    }

    /**
     * @inheritdoc
     */
    protected function getEncoder(): EncoderInterface
    {
        if ($this->encoder) {
            return $this->encoder;
        }

        return $this->encoder = $this->createEncoder();
    }

    /**
     * Create a new and configured encoder.
     *
     * @return Encoder
     */
    protected function createEncoder(): Encoder
    {
        $encoder = $this
            ->getCodec()
            ->getEncoder();

        $encoder
            ->withUrlPrefix($this->getUrlPrefix())
            ->withEncodingParameters($this->parameters);

        return $encoder;
    }

    /**
     * @inheritdoc
     */
    protected function getMediaType(): MediaTypeInterface
    {
        return $this->getCodec()->getEncodingMediaType();
    }

    /**
     * @return Codec
     */
    protected function getCodec(): Codec
    {
        if ($this->codec) {
            return $this->codec;
        }

        return $this->codec = $this->getDefaultCodec();
    }

    /**
     * @return Codec
     */
    protected function getDefaultCodec(): Codec
    {
        if ($this->route->hasCodec()) {
            return $this->route->getCodec();
        }

        return $this->api->getDefaultCodec();
    }

    /**
     * @return string
     */
    protected function getUrlPrefix(): string
    {
        return $this->api->getUrl()->toString();
    }

    /**
     * @return QueryParametersInterface|null
     */
    protected function getEncodingParameters(): ?QueryParametersInterface
    {
        return $this->parameters;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->api->getContainer();
    }

    /**
     * Create HTTP response.
     *
     * @param string|null $content
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    protected function createResponse(?string $content, int $statusCode, array $headers = []): Response
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
    protected function isNoContent($resource, $links, $meta): bool
    {
        return is_null($resource) && empty($links) && empty($meta);
    }

    /**
     * Does the data represent an asynchronous process?
     *
     * @param $data
     * @return bool
     */
    protected function isAsync($data): bool
    {
        return $data instanceof AsynchronousProcess;
    }

    /**
     * @param $resource
     * @return LinkInterface
     */
    private function getResourceSelfLink($resource): LinkInterface
    {
        $schemaProvider = $this
            ->getContainer()
            ->getSchema($resource);

        return $schemaProvider->getSelfSubLink($resource);
    }

    /**
     * @param PageInterface $page
     * @param $meta
     * @param $links
     * @return array
     */
    private function extractPage(PageInterface $page, $meta, $links): array
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
    private function mergePageMeta($existing, PageInterface $page): array
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
    private function mergePageLinks(array $existing, PageInterface $page): array
    {
        return array_replace($existing, array_filter([
            DocumentInterface::KEYWORD_FIRST => $page->getFirstLink(),
            DocumentInterface::KEYWORD_PREV => $page->getPreviousLink(),
            DocumentInterface::KEYWORD_NEXT => $page->getNextLink(),
            DocumentInterface::KEYWORD_LAST => $page->getLastLink(),
        ]));
    }

}
