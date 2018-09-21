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

namespace CloudCreativity\LaravelJsonApi\Client;

use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;

/**
 * Class ClientSerializer
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ClientSerializer
{

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var HttpFactoryInterface
     */
    protected $factory;

    /**
     * @var bool
     */
    private $links;

    /**
     * @var array|null
     */
    private $includePaths;

    /**
     * @var bool
     */
    private $compoundDocuments;

    /**
     * @var array|null
     */
    private $fieldsets;

    /**
     * ClientSerializer constructor.
     *
     * @param SerializerInterface $serializer
     * @param HttpFactoryInterface $factory
     */
    public function __construct(SerializerInterface $serializer, HttpFactoryInterface $factory)
    {
        $this->serializer = $serializer;
        $this->factory = $factory;
        $this->links = false;
        $this->includePaths = null;
        $this->fieldsets = null;
        $this->compoundDocuments = false;
    }

    /**
     * @param bool $links
     * @return ClientSerializer
     */
    public function withLinks($links = true)
    {
        $copy = clone $this;
        $copy->links = $links;

        return $copy;
    }

    /**
     * @param string ...$paths
     * @return ClientSerializer
     */
    public function withIncludePaths(...$paths)
    {
        $copy = clone $this;
        $copy->includePaths = $paths ?: null;

        return $copy;
    }

    /**
     * @param bool $bool
     * @return ClientSerializer
     */
    public function withCompoundDocuments($bool = true)
    {
        $copy = clone $this;
        $copy->compoundDocuments = $bool;

        return $copy;
    }

    /**
     * @param string $resourceType
     * @param string|string[] $fields
     * @return ClientSerializer
     */
    public function withFieldsets($resourceType, $fields)
    {
        $fieldsets = $this->fieldsets ?: [];

        if ($fields) {
            $fieldsets[$resourceType] = (array) $fields;
        } else {
            unset($fieldsets[$resourceType]);
        }

        $copy = clone $this;
        $copy->fieldsets = $fieldsets ?: null;

        return $copy;
    }

    /**
     * Serialize a domain record.
     *
     * @param $record
     * @param mixed|null $meta
     * @param mixed|null $links
     * @return array
     */
    public function serialize($record, $meta = null, array $links = [])
    {
        $serializer = clone $this->serializer;
        $serializer->withMeta($meta)->withLinks($links);
        $serialized = $serializer->serializeData($record, $this->createEncodingParameters());
        $resourceLinks = null;

        if (empty($serialized['data']['id'])) {
            unset($serialized['data']['id']);
            $resourceLinks = false; // links will not be valid so strip them out.
        }

        $resource = $this->parsePrimaryResource($serialized['data'], $resourceLinks);
        $document = ['data' => $resource];

        if (isset($serialized['included']) && $this->doesSerializeCompoundDocuments()) {
            $document['included'] = $this->parseIncludedResources($serialized['included']);
        }

        return $document;
    }

    /**
     * Serialize related record(s).
     *
     * @param object|iterable|array|null $related
     * @param mixed|null $meta
     * @param array $links
     * @return array
     */
    public function serializeRelated($related, $meta = null, array $links = [])
    {
        $serializer = clone $this->serializer;
        $serializer->withMeta($meta)->withLinks($links);

        return $serializer->serializeIdentifiers($related);
    }

    /**
     * @param array $resource
     * @param bool|null $links
     * @return array
     */
    protected function parsePrimaryResource(array $resource, $links = null)
    {
        return $this->parseResource($resource, true, $links);
    }

    /**
     * @param array $resources
     * @return Collection
     */
    protected function parseIncludedResources(array $resources)
    {
        return $this->parseResources($resources, false);
    }

    /**
     * @param array $resources
     * @param bool $primary
     * @return Collection
     */
    protected function parseResources(array $resources, $primary = false)
    {
        return collect($resources)->map(function (array $resource) use ($primary) {
            return $this->parseResource($resource, $primary);
        });
    }

    /**
     * @param array $resource
     * @param bool $primary
     * @param bool|null $links
     * @return array
     */
    protected function parseResource(array $resource, $primary = false, $links = null)
    {
        if (false === $links || $this->doesRemoveLinks()) {
            unset($resource['links']);
        }

        $relationships = isset($resource['relationships']) ?
            $this->parseRelationships($resource['relationships'], $primary, $links) : [];

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
     * @param bool|null $links
     * @return array
     */
    protected function parseRelationships(array $relationships, $primary = false, $links = null)
    {
        return collect($relationships)->reject(function (array $relation) use ($primary) {
            return $primary && !array_key_exists('data', $relation);
        })->map(function (array $relation) use ($primary, $links) {
            return $this->parseRelationship($relation, $primary, $links);
        })->filter()->all();
    }

    /**
     * @param array $relationship
     * @param bool $primary
     * @param null $links
     * @return array|null
     */
    protected function parseRelationship(array $relationship, $primary = false, $links = null)
    {
        if (false === $links || $this->doesRemoveLinks()) {
            unset($relationship['links']);
        }

        return $relationship ?: null;
    }

    /**
     * @return bool
     */
    protected function doesSerializeCompoundDocuments()
    {
        return $this->compoundDocuments;
    }

    /**
     * @return bool
     */
    protected function doesRemoveLinks()
    {
        return !$this->links;
    }

    /**
     * @return EncodingParametersInterface
     */
    protected function createEncodingParameters()
    {
        return $this->factory->createQueryParameters(
            $this->includePaths,
            $this->fieldsets
        );
    }
}
