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

use CloudCreativity\LaravelJsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GuzzleClient
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class GuzzleClient extends AbstractClient
{

    /**
     * @var Client
     */
    private $http;

    /**
     * GuzzleClient constructor.
     *
     * @param FactoryInterface $factory
     * @param Client $http
     * @param ContainerInterface $schemas
     * @param ClientSerializer $serializer
     */
    public function __construct(
        FactoryInterface $factory,
        Client $http,
        ContainerInterface $schemas,
        ClientSerializer $serializer
    ) {
        parent::__construct($factory, $schemas, $serializer);
        $this->http = $http;
    }

    /**
     * @inheritdoc
     */
    public function index($resourceType, EncodingParametersInterface $parameters = null, array $options = [])
    {
        $options = $this->mergeOptions([
            'headers' => $this->jsonApiHeaders(false),
            'query' => $parameters ? $this->parseSearchQuery($parameters) : null,
        ], $options);

        return $this->request('GET', $this->resourceUri($resourceType), $options);
    }

    /**
     * @inheritdoc
     */
    public function create(
        $record,
        EncodingParametersInterface $parameters = null,
        array $options = []
    ) {
        return $this->sendRecord('POST', $this->serializer->serialize($record), $parameters, $options);
    }

    /**
     * @inheritdoc
     */
    public function read(
        $resourceType,
        $resourceId,
        EncodingParametersInterface $parameters = null,
        array $options = []
    ) {
        $uri = $this->resourceUri($resourceType, $resourceId);
        $options = $this->mergeOptions([
            'headers' => $this->jsonApiHeaders(false),
            'query' => $parameters ? $this->parseQuery($parameters) : null,
        ], $options);

        return $this->request('GET', $uri, $options);
    }

    /**
     * @inheritdoc
     */
    public function update(
        $record,
        EncodingParametersInterface $parameters = null,
        array $options = []
    ) {
        return $this->sendRecord('PATCH', $this->serializer->serialize($record), $parameters, $options);
    }

    /**
     * @inheritdoc
     */
    public function delete($record, array $options = [])
    {
        $options = $this->mergeOptions([
            'headers' => $this->jsonApiHeaders(false)
        ], $options);

        return $this->request('DELETE', $this->recordUri($record), $options);
    }

    /**
     * @param $method
     * @param array $serializedRecord
     *      the encoded record
     * @param EncodingParametersInterface|null $parameters
     * @param array $options
     * @return ResponseInterface
     */
    protected function sendRecord(
        $method,
        array $serializedRecord,
        EncodingParametersInterface $parameters = null,
        array $options = []
    ) {
        $resourceType = $serializedRecord['data']['type'];

        if ('POST' === $method) {
            $uri = $this->resourceUri($resourceType);
        } else {
            $resourceId = isset($serializedRecord['data']['id']) ? $serializedRecord['data']['id'] : null;
            $uri = $this->resourceUri($resourceType, $resourceId);
        }

        $options = $this->mergeOptions([
            'headers' => $this->jsonApiHeaders(true),
            'query' => $parameters ? $this->parseQuery($parameters) : null,
        ], $options);

        $options['json'] = $serializedRecord;

        return $this->request($method, $uri, $options);
    }

    /**
     * @param array $new
     * @param array $existing
     * @return array
     */
    protected function mergeOptions(array $new, array $existing)
    {
        return array_replace_recursive($new, $existing);
    }

    /**
     * @param $method
     * @param $uri
     * @param array $options
     * @return ResponseInterface
     * @throws ClientException
     */
    protected function request($method, $uri, array $options = [])
    {
        $request = new Request($method, $uri);

        try {
            return $this->http->send($request, $options);
        } catch (RequestException $ex) {
            throw new ClientException($request, $ex->getResponse(), $ex);
        } catch (TransferException $ex) {
            throw new ClientException($request, null, $ex);
        }
    }

}
