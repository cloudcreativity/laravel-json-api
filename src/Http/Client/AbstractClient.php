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
use Illuminate\Support\Collection;
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
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var array|null
     */
    protected $includePaths;

    /**
     * @var bool
     */
    protected $compoundDocument;

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
        $this->serializer = $serializer;
        $this->compoundDocument = false;
        $this->links = false;
    }

    /**
     * @inheritDoc
     */
    public function withIncludePaths($includePaths = null, $compoundDocument = false)
    {
        $copy = clone $this;
        $copy->includePaths = $includePaths ? (array) $includePaths : null;
        $copy->compoundDocument = $compoundDocument;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function withFields(array $fields = null)
    {
        $copy = clone $this;
        $copy->fieldSets = $fields;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function withLinks()
    {
        $copy = clone $this;
        $copy->links = true;

        return $copy;
    }

    /**
     * @param $record
     * @return array
     */
    protected function serializeRecord($record)
    {
        $parameters = null;
        $fields = null;

        if ($this->fieldSets) {
            $resourceType = $this->schemas->getSchema($record)->getResourceType();
            $fields = [$resourceType => $this->fieldSets];
        }

        $parameters = $this->factory->createQueryParameters(
            $this->includePaths,
            $fields
        );

        $document = $this->serializer->serializeData($record, $parameters);

        /** Remove resource links and included resources. */
        if ($this->doesNotIncludeLinks()) {
            unset($document['links']);
        }

        if ($this->doesNotSendCompoundDocuments()) {
            unset($document['included']);
        } else if (isset($document['included'])) {
            $document['included'] = collect($document['included'])->map(function (array $resource) {
                return $this->parseSerializedResource($resource, false);
            })->all();
        }

        $document['data'] = $this->parseSerializedResource($document['data']);

        return $document;
    }

    /**
     * @param array $resource
     * @param bool $primary
     * @return array
     */
    protected function parseSerializedResource(array $resource, $primary = true)
    {
        if ($this->doesNotIncludeLinks()) {
            unset($resource['links']);
        }

        $relationships = isset($resource['relationships']) ?
            $this->parseSerializedRelationships($resource['relationships'], $primary) : [];

        if ($relationships) {
            $resource['relationships'] = $relationships;
        } else {
            unset($resource['relationships']);
        }

        return $resource;
    }

    /**
     * @param array $relationships
     * @param bool $primary
     * @return array
     */
    protected function parseSerializedRelationships(array $relationships, $primary = true)
    {
        return collect($relationships)->reject(function (array $relation) use ($primary) {
            return $primary && !isset($relation['data']);
        })->when($this->doesNotIncludeLinks(), function (Collection $relationships) {
            return $relationships->map(function (array $relation) {
                unset($relation['links']);
                return $relation ?: null;
            })->filter();
        })->all();
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
