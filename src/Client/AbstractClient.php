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

use CloudCreativity\LaravelJsonApi\Contracts\Client\ClientInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
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
     * @param EncodingParametersInterface|null $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    abstract protected function request(
        $method,
        $uri,
        array $payload = null,
        EncodingParametersInterface $parameters = null
    );

    /**
     * AbstractClient constructor.
     *
     * @param ContainerInterface $schemas
     * @param ClientSerializer $serializer
     */
    public function __construct(ContainerInterface $schemas, ClientSerializer $serializer)
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
    public function query($resourceType, EncodingParametersInterface $parameters = null)
    {
        return $this->request('GET', $this->resourceUri($resourceType), null, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function create($resourceType, array $payload, EncodingParametersInterface $parameters = null)
    {
        $uri = $this->resourceUri($resourceType);

        return $this->request('POST', $uri, $payload, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function createRecord($record, EncodingParametersInterface $parameters = null)
    {
        list($resourceType) = $this->resourceIdentifier($record);

        return $this->create($resourceType, $this->serializer->serialize($record), $parameters);
    }

    /**
     * @inheritdoc
     */
    public function read($resourceType, $resourceId, EncodingParametersInterface $parameters = null)
    {
        $uri = $this->resourceUri($resourceType, $resourceId);

        return $this->request('GET', $uri, null, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function readRecord($record, EncodingParametersInterface $parameters = null)
    {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->read($resourceType, $resourceId, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function update(
        $resourceType,
        $resourceId,
        array $payload,
        EncodingParametersInterface $parameters = null
    ) {
        $uri = $this->resourceUri($resourceType, $resourceId);

        return $this->request('PATCH', $uri, $payload, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function updateRecord($record, EncodingParametersInterface $parameters = null)
    {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->update($resourceType, $resourceId, $this->serializer->serialize($record), $parameters);
    }

    /**
     * @inheritdoc
     */
    public function delete($resourceType, $resourceId)
    {
        $uri = $this->resourceUri($resourceType, $resourceId);

        return $this->request('DELETE', $uri);
    }

    /**
     * @inheritDoc
     */
    public function deleteRecord($record)
    {
        list ($resourceType, $resourceId) = $this->resourceIdentifier($record);

        return $this->delete($resourceType, $resourceId);
    }

    /**
     * @param object $record
     * @return array
     */
    protected function resourceIdentifier($record)
    {
        $schema = $this->schemas->getSchema($record);

        return [$schema->getResourceType(), $schema->getId($record)];
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
}