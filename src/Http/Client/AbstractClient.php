<?php

/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Client;

use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Client\ClientInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;

/**
 * Class AbstractClient
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractClient implements ClientInterface
{

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var ContainerInterface
     */
    protected $schemas;

    /**
     * @var ClientSerializer
     */
    protected $serializer;

    /**
     * @var array|null
     */
    protected $fieldSets;

    /**
     * @var bool
     */
    protected $links;

    /**
     * AbstractClient constructor.
     *
     * @param FactoryInterface $factory
     * @param ContainerInterface $schemas
     * @param SerializerInterface $serializer
     */
    public function __construct(
        FactoryInterface $factory,
        ContainerInterface $schemas,
        SerializerInterface $serializer
    ) {
        $this->factory = $factory;
        $this->schemas = $schemas;
        $this->serializer = new ClientSerializer($serializer, $factory);
        $this->links = false;
    }

    /**
     * @inheritDoc
     */
    public function withIncludePaths(...$includePaths)
    {
        $copy = clone $this;
        $copy->serializer = $copy->serializer->withIncludePaths(...$includePaths);

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function withFields($resourceType, ...$fields)
    {
        $copy = clone $this;
        $copy->serializer = $copy->serializer->withFieldsets($resourceType, ...$fields);

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function withCompoundDocuments()
    {
        $copy = clone $this;
        $copy->serializer = $copy->serializer->withCompoundDocuments(true);

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function withLinks()
    {
        $copy = clone $this;
        $copy->serializer = $copy->serializer->withLinks(true);

        return $copy;
    }

    /**
     * Get the path for a record.
     *
     * @param object $record
     * @return string
     */
    protected function recordUri($record)
    {
        $schema = $this->schemas->getSchema($record);

        return $this->resourceUri($schema->getResourceType(), $schema->getId($record));
    }

    /**
     * Get the path for a resource type, or resource type and id.
     *
     * @param string $resourceType
     * @param string|null $resourceId
     * @return string
     */
    protected function resourceUri($resourceType, $resourceId = null)
    {
        return $resourceId ? "$resourceType/$resourceId" : $resourceType;
    }

    /**
     * @param bool $body
     *      whether HTTP request body is being sent.
     * @return array
     */
    protected function jsonApiHeaders($body = false)
    {
        $headers = ['Accept' => MediaType::JSON_API_MEDIA_TYPE];

        if ($body) {
            $headers['Content-Type'] = MediaType::JSON_API_MEDIA_TYPE;
        }

        return $headers;
    }

    /**
     * @param EncodingParametersInterface $parameters
     * @return array
     */
    protected function parseQuery(EncodingParametersInterface $parameters)
    {
        return array_filter(array_merge((array) $parameters->getUnrecognizedParameters(), [
            QueryParametersParserInterface::PARAM_INCLUDE =>
                implode(',', (array) $parameters->getIncludePaths()),
            QueryParametersParserInterface::PARAM_FIELDS =>
                $this->parseQueryFieldsets((array) $parameters->getFieldSets()),
        ]));
    }

    /**
     * @param EncodingParametersInterface $parameters
     * @return array
     */
    protected function parseSearchQuery(EncodingParametersInterface $parameters)
    {
        return array_filter(array_merge($this->parseQuery($parameters), [
            QueryParametersParserInterface::PARAM_SORT =>
                implode(',', (array) $parameters->getSortParameters()),
            QueryParametersParserInterface::PARAM_PAGE =>
                $parameters->getPaginationParameters(),
            QueryParametersParserInterface::PARAM_FILTER =>
                $parameters->getFilteringParameters(),
        ]));
    }

    /**
     * @return bool
     */
    protected function doesIncludeLinks()
    {
        return $this->links;
    }

    /**
     * @return bool
     */
    protected function doesNotIncludeLinks()
    {
        return !$this->doesIncludeLinks();
    }

    /**
     * @return bool
     */
    protected function doesSendCompoundDocuments()
    {
        return $this->compoundDocument;
    }

    /**
     * @return bool
     */
    protected function doesNotSendCompoundDocuments()
    {
        return !$this->doesSendCompoundDocuments();
    }

    /**
     * @param array $fieldsets
     * @return array
     */
    private function parseQueryFieldsets(array $fieldsets)
    {
        return array_map(function ($values) {
            return implode(',', (array) $values);
        }, $fieldsets);
    }
}
