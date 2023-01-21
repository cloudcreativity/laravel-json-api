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

namespace CloudCreativity\LaravelJsonApi\Client;

use CloudCreativity\LaravelJsonApi\Contracts\Client\ClientInterface;
use CloudCreativity\LaravelJsonApi\Http\Query\QueryParameters;
use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AbstractClient
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractClient implements ClientInterface
{

    /**
     * @var SchemaContainerInterface
     */
    protected SchemaContainerInterface $schemas;

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
     * @var array
     */
    protected $options;

    /**
     * Send a request.
     *
     * @param string $method
     * @param string $uri
     * @param array|null $payload
     *      the JSON API payload, or null if no payload to send.
     * @param array $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    abstract protected function request(
        $method,
        $uri,
        array $payload = null,
        array $parameters = []
    );

    /**
     * AbstractClient constructor.
     *
     * @param SchemaContainerInterface $schemas
     * @param ClientSerializer $serializer
     */
    public function __construct(SchemaContainerInterface $schemas, ClientSerializer $serializer)
    {
        $this->schemas = $schemas;
        $this->serializer = $serializer;
        $this->links = false;
        $this->options = [];
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
    public function withFields($resourceType, $fields)
    {
        $copy = clone $this;
        $copy->serializer = $copy->serializer->withFieldsets($resourceType, $fields);

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
     * @inheritDoc
     */
    public function withOptions(array $options)
    {
        $copy = clone $this;
        $copy->options = array_replace_recursive($this->options, $options);

        return $copy;
    }


    /**
     * @inheritdoc
     */
    public function query($resourceType, $parameters = [])
    {
        return $this->request(
            'GET',
            $this->resourceUri($resourceType),
            null,
            $this->queryParameters($parameters)
        );
    }

    /**
     * @inheritDoc
     */
    public function create($resourceType, array $payload, $parameters = [])
    {
        return $this->request(
            'POST',
            $this->resourceUri($resourceType),
            $payload,
            $this->queryParameters($parameters)
        );
    }

    /**
     * @inheritdoc
     */
    public function createRecord($record, $parameters = [])
    {
        list($resourceType) = $this->resourceIdentifier($record);

        return $this->create($resourceType, $this->serializer->serialize($record), $parameters);
    }

    /**
     * @inheritdoc
     */
    public function read($resourceType, $resourceId, $parameters = [])
    {
        return $this->request(
            'GET',
            $this->resourceUri($resourceType, $resourceId),
            null,
            $this->queryParameters($parameters)
        );
    }

    /**
     * @inheritDoc
     */
    public function readRecord($record, $parameters = [])
    {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->read($resourceType, $resourceId, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function update($resourceType, $resourceId, array $payload, $parameters = [])
    {
        return $this->request(
            'PATCH',
            $this->resourceUri($resourceType, $resourceId),
            $payload,
            $this->queryParameters($parameters)
        );
    }

    /**
     * @inheritdoc
     */
    public function updateRecord($record, $parameters = [])
    {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->update($resourceType, $resourceId, $this->serializer->serialize($record), $parameters);
    }

    /**
     * @inheritdoc
     */
    public function delete($resourceType, $resourceId, $parameters = [])
    {
        return $this->request(
            'DELETE',
            $this->resourceUri($resourceType, $resourceId),
            null,
            $this->queryParameters($parameters)
        );
    }

    /**
     * @inheritDoc
     */
    public function deleteRecord($record, $parameters = [])
    {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->delete($resourceType, $resourceId, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function readRelated($resourceType, $resourceId, $relationship, $parameters = [])
    {
        return $this->request(
            'GET',
            $this->relatedUri($resourceType, $resourceId, $relationship),
            null,
            $this->queryParameters($parameters)
        );
    }

    /**
     * @inheritDoc
     */
    public function readRecordRelated($record, $relationship, $parameters = [])
    {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->readRelated($resourceType, $resourceId, $relationship, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function readRelationship($resourceType, $resourceId, $relationship, $parameters = [])
    {
        return $this->request(
            'GET',
            $this->relationshipUri($resourceType, $resourceId, $relationship),
            null,
            $this->queryParameters($parameters)
        );
    }

    /**
     * @inheritDoc
     */
    public function readRecordRelationship($record, $relationship, $parameters = [])
    {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->readRelationship($resourceType, $resourceId, $relationship, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function replaceRelationship(
        $resourceType,
        $resourceId,
        $relationship,
        array $payload,
        $parameters = []
    ) {
        return $this->request(
            'PATCH',
            $this->relationshipUri($resourceType, $resourceId, $relationship),
            $payload,
            $this->queryParameters($parameters)
        );
    }

    /**
     * @inheritDoc
     */
    public function replaceRecordRelationship($record, $related, $relationship, $parameters = [])
    {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->replaceRelationship(
            $resourceType,
            $resourceId,
            $relationship,
            $this->serializer->serializeRelated($related),
            $parameters
        );
    }

    /**
     * @inheritDoc
     */
    public function addToRelationship($resourceType, $resourceId, $relationship, array $payload, $parameters = [])
    {
        return $this->request(
            'POST',
            $this->relationshipUri($resourceType, $resourceId, $relationship),
            $payload,
            $this->queryParameters($parameters)
        );
    }

    /**
     * @inheritDoc
     */
    public function addToRecordRelationship($record, $related, $relationship, $parameters = [])
    {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->addToRelationship(
            $resourceType,
            $resourceId,
            $relationship,
            $this->serializer->serializeRelated($related),
            $parameters
        );
    }

    /**
     * @inheritDoc
     */
    public function removeFromRelationship(
        $resourceType,
        $resourceId,
        $relationship,
        array $payload,
        $parameters = []
    ) {
        return $this->request(
            'DELETE',
            $this->relationshipUri($resourceType, $resourceId, $relationship),
            $payload,
            $this->queryParameters($parameters)
        );
    }

    /**
     * @inheritDoc
     */
    public function removeFromRecordRelationship(
        $record,
        $related,
        $relationship,
        $parameters = []
    ) {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->removeFromRelationship(
            $resourceType,
            $resourceId,
            $relationship,
            $this->serializer->serializeRelated($related),
            $parameters
        );
    }

    /**
     * @param object $record
     * @return array
     */
    protected function resourceIdentifier($record)
    {
        $schema = $this->schemas->getSchema($record);

        return [$schema->getType(), $schema->getId($record)];
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
     * Get the path for reading the related resource in a relationship.
     *
     * @param $resourceType
     * @param $resourceId
     * @param $relationship
     * @return string
     */
    protected function relatedUri($resourceType, $resourceId, $relationship)
    {
        return $this->resourceUri($resourceType, $resourceId) . '/' . $relationship;
    }

    /**
     * Get the path for a resource's relationship.
     *
     * @param $resourceType
     * @param $resourceId
     * @param $relationship
     * @return string
     */
    protected function relationshipUri($resourceType, $resourceId, $relationship)
    {
        return $this->resourceUri($resourceType, $resourceId) . '/relationships/' . $relationship;
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
     * @param QueryParametersInterface|array $parameters
     * @return array
     */
    protected function queryParameters($parameters)
    {
        if ($parameters instanceof QueryParametersInterface) {
            return QueryParameters::cast($parameters)->toArray();
        }

        return $parameters;
    }
}
