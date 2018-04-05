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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Http\Client;

use CloudCreativity\JsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\JsonApi\Contracts\Http\Client\ClientInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Document\Error;
use CloudCreativity\JsonApi\Encoder\Encoder;
use CloudCreativity\JsonApi\Factories\Factory;
use CloudCreativity\JsonApi\Http\Responses\Response as ClientResponse;
use CloudCreativity\JsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaProviderInterface;
use Neomerx\JsonApi\Document\Link;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;
use Neomerx\JsonApi\Encoder\Parameters\SortParameter;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use function GuzzleHttp\Psr7\parse_query;

/**
 * Class GuzzleClientTest
 *
 * @package CloudCreativity\JsonApi
 */
class GuzzleClientTest extends TestCase
{

    /**
     * @var Mock
     */
    private $encoder;

    /**
     * @var object
     */
    private $record;

    /**
     * @var MockHandler
     */
    private $mock;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->record = (object) [
            'type' => 'posts',
            'id' => '1',
            'attributes' => ['title' => 'Hello World'],
        ];

        /** @var SerializerInterface $serializer */
        $serializer = $this->encoder = $this->createMock(SerializerInterface::class);

        $schema = $this->createMock(SchemaProviderInterface::class);
        $schema->method('getResourceType')->willReturn('posts');
        $schema->method('getId')->with($this->record)->willReturn('1');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('getSchema')->with($this->record)->willReturn($schema);

        $http = new Client([
            'handler' => HandlerStack::create($this->mock = new MockHandler()),
            'base_uri' => 'http://localhost/api/v1/',
        ]);

        /** @var ContainerInterface $container */
        $factory = new Factory();
        $this->client = $factory->createClient($http, $container, $serializer);
    }

    public function testIndex()
    {
        $this->willSeeRecords();
        $response = $this->client->index('posts');
        $this->assertSame(200, $response->getPsrResponse()->getStatusCode());
        $this->assertRequested('GET', '/posts');
        $this->assertHeader('Accept', 'application/vnd.api+json');

        $identifier = ResourceIdentifier::create('posts', '1');
        $this->assertInstanceOf(
            ResourceObjectInterface::class,
            $response->getDocument()->getResources()->get($identifier)
        );
    }

    public function testIndexWithParameters()
    {
        $parameters = new EncodingParameters(
            ['author', 'site'],
            ['author' => ['first-name', 'surname'], 'site' => ['uri']],
            [new SortParameter('created-at', false), new SortParameter('author', true)],
            ['number' => 1, 'size' => 15],
            ['author' => 99],
            ['foo' => 'bar']
        );

        $this->willSeeRecords();
        $this->client->index('posts', $parameters);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'fields[author]' => 'first-name,surname',
            'fields[site]' => 'uri',
            'sort' => '-created-at,author',
            'page[number]' => '1',
            'page[size]' => '15',
            'filter[author]' => '99',
            'foo' => 'bar'
        ]);
    }

    public function testIndexWithOptions()
    {
        $this->willSeeRecords();
        $this->client->index('posts', null, [
            'headers' => [
                'X-Foo' => 'Bar'
            ],
        ]);

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testIndexError()
    {
        $this->willSeeErrors();
        $this->expectException(JsonApiException::class);
        $this->client->index('posts');
    }

    public function testCreateWithoutId()
    {
        $this->record->id = null;
        $this->willSerializeRecord()->willSeeRecord(201);
        $response = $this->client->create($this->record);

        $this->assertSame(201, $response->getPsrResponse()->getStatusCode());
        $this->assertResponseResource($response);
        $this->assertRequested('POST', '/posts');
        $this->assertRequestSentRecord();
        $this->assertHeader('Accept', 'application/vnd.api+json');
        $this->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    public function testCreateWithClientGeneratedId()
    {
        $this->willSerializeRecord()->willSeeRecord(201);
        $this->client->create($this->record);
        $this->assertRequested('POST', '/posts');
    }

    public function testCreateWithParameters()
    {
        $parameters = new EncodingParameters(
            ['author', 'site'],
            ['author' => ['first-name', 'surname'], 'site' => ['uri']],
            null,
            null,
            null,
            ['foo' => 'bar']
        );

        $this->willSerializeRecord()->willSeeRecord(201);
        $this->client->create($this->record, $parameters);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'fields[author]' => 'first-name,surname',
            'fields[site]' => 'uri',
            'foo' => 'bar'
        ]);
    }

    public function testCreateWithNoContentResponse()
    {
        $this->willSerializeRecord()->appendResponse(204);
        $response = $this->client->create($this->record);
        $this->assertSame(204, $response->getPsrResponse()->getStatusCode());
        $this->assertNull($response->getDocument());
    }

    public function testCreateWithOptions()
    {
        $this->willSerializeRecord()->willSeeRecord(201);
        $this->client->create($this->record, null, [
            'headers' => ['X-Foo' => 'Bar'],
        ]);

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    public function testCreateError()
    {
        $this->willSerializeRecord()->willSeeErrors();
        $this->expectException(JsonApiException::class);
        $this->client->create($this->record);
    }

    public function testRead()
    {
        $this->willSeeRecord();
        $response = $this->client->read('posts', '1');

        $this->assertSame(200, $response->getPsrResponse()->getStatusCode());
        $this->assertResponseResource($response);
        $this->assertRequested('GET', '/posts/1');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testReadWithParameters()
    {
        $parameters = new EncodingParameters(
            ['author', 'site'],
            ['author' => ['first-name', 'surname'], 'site' => ['uri']]
        );

        $this->willSeeRecord();
        $this->client->read('posts', '1', $parameters);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'fields[author]' => 'first-name,surname',
            'fields[site]' => 'uri',
        ]);
    }

    public function testReadWithOptions()
    {
        $this->willSeeRecord();
        $this->client->read('posts', '1', null, [
            'headers' => [
                'X-Foo' => 'Bar',
            ],
        ]);

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testReadError()
    {
        $this->willSeeErrors();
        $this->expectException(JsonApiException::class);
        $this->client->read('posts', '1');
    }

    public function testUpdate()
    {
        $this->willSerializeRecord()->willSeeRecord();
        $response = $this->client->update($this->record);

        $this->assertSame(200, $response->getPsrResponse()->getStatusCode());
        $this->assertResponseResource($response);
        $this->assertRequested('PATCH', '/posts/1');
        $this->assertRequestSentRecord();
        $this->assertHeader('Accept', 'application/vnd.api+json');
        $this->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    public function testUpdateWithFieldsets()
    {
        $expected = new EncodingParameters(
            null,
            ['posts' => $fields = ['content', 'published-at']]
        );

        $this->willSerializeRecord($expected)->willSeeRecord();
        $this->client->update($this->record, $fields);
    }

    public function testUpdateWithParameters()
    {
        $parameters = new EncodingParameters(
            ['author', 'site'],
            ['author' => ['first-name', 'surname'], 'site' => ['uri']]
        );

        $this->willSerializeRecord()->willSeeRecord(201);
        $this->client->update($this->record, [], $parameters);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'fields[author]' => 'first-name,surname',
            'fields[site]' => 'uri',
        ]);
    }

    public function testUpdateWithNoContentResponse()
    {
        $this->willSerializeRecord()->appendResponse(204);
        $response = $this->client->update($this->record);
        $this->assertSame(204, $response->getPsrResponse()->getStatusCode());
        $this->assertNull($response->getDocument());
    }

    public function testUpdateWithOptions()
    {
        $this->willSerializeRecord()->willSeeRecord();
        $this->client->update($this->record, null, null, [
            'headers' => [
                'X-Foo' => 'Bar',
            ],
        ]);

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    public function testUpdateError()
    {
        $this->willSerializeRecord()->willSeeErrors();
        $this->expectException(JsonApiException::class);
        $this->client->update($this->record);
    }

    public function testDelete()
    {
        $this->appendResponse(204);
        $response = $this->client->delete($this->record);
        $this->assertSame(204, $response->getPsrResponse()->getStatusCode());
        $this->assertNull($response->getDocument());
        $this->assertRequested('DELETE', '/posts/1');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testDeleteWithOptions()
    {
        $this->appendResponse(204);
        $this->client->delete($this->record, [
            'headers' => [
                'X-Foo' => 'Bar',
            ],
        ]);

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testErrorResponse()
    {
        $this->willSeeErrors([], 422);

        try {
            $this->client->delete($this->record);
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame(422, $ex->getHttpCode());
            $this->assertEmpty($ex->getErrors()->getArrayCopy());
            $this->assertInstanceOf(BadResponseException::class, $ex->getPrevious());
        }
    }

    public function testErrorResponseWithErrorObjects()
    {
        $expected = new Error(
            "536d04b6-3a76-43ed-8c2f-9e60e6e68aa1",
            ['about' => new Link("http://localhost/errors/server", null, true)],
            500,
            "server",
            "Server Error",
            "An unexpected error occurred.",
            ["pointer" => "/"],
            ["foo" => "bar"]
        );

        /** @var Encoder $encoder */
        $encoder = Encoder::instance();
        $this->willSeeErrors($encoder->serializeErrors([$expected]), 500);

        try {
            $this->client->delete($this->record);
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertEquals([$expected], $ex->getErrors()->getArrayCopy());
        }
    }

    /**
     * @param EncodingParametersInterface|null $parameters
     * @return $this
     */
    private function willSerializeRecord(EncodingParametersInterface $parameters = null)
    {
        $this->encoder
            ->expects($this->once())
            ->method('serializeData')
            ->with($this->record, $parameters)
            ->willReturn(['data' => (array) $this->record]);

        return $this;
    }

    /**
     * @return $this
     */
    private function willSeeRecords()
    {
        $this->appendResponse(200, ['Content-Type' => 'application/vnd.api+json'], [
            'data' => [(array) $this->record],
        ]);

        return $this;
    }

    /**
     * @param int $status
     * @return $this
     */
    private function willSeeRecord($status = 200)
    {
        $copy = clone $this->record;
        $copy->id = $copy->id ?: '1';

        $this->appendResponse($status, ['Content-Type' => 'application/vnd.api+json'], [
            'data' => (array) $copy,
        ]);

        return $this;
    }

    /**
     * @param array $errors
     * @param int $status
     * @return $this
     */
    private function willSeeErrors(array $errors = null, $status = 400)
    {
        $errors = $errors ?: ['errors' => []];
        $this->appendResponse($status, ['Content-Type' => 'application/vnd.api+json'], $errors);

        return $this;
    }

    /**
     * @param int $status
     * @param array $headers
     * @param array|null $body
     * @return $this
     */
    private function appendResponse($status = 200, array $headers = [], array $body = null)
    {
        if (is_array($body)) {
            $body = json_encode($body);
            $headers['Content-Length'] = strlen($body);
        }

        $this->mock->append(new Response($status, $headers, $body));

        return $this;
    }

    /**
     * @return void
     */
    private function assertRequestSentRecord()
    {
        $expected = json_encode(['data' => (array) $this->record]);
        $request = $this->mock->getLastRequest();
        $this->assertJsonStringEqualsJsonString($expected, (string) $request->getBody());
    }

    /**
     * @param $method
     * @param $path
     * @return void
     */
    private function assertRequested($method, $path)
    {
        $uri = 'http://localhost/api/v1' . $path;
        $request = $this->mock->getLastRequest();
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, (string) $request->getUri(), 'request uri');
    }

    /**
     * @param $key
     * @param $expected
     * @return void
     */
    private function assertHeader($key, $expected)
    {
        $request = $this->mock->getLastRequest();
        $actual = $request->getHeaderLine($key);
        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $expected
     * @return void
     */
    private function assertQueryParameters(array $expected)
    {
        $query = $this->mock->getLastRequest()->getUri()->getQuery();
        $this->assertEquals($expected, parse_query($query));
    }

    /**
     * @param ClientResponse $response
     */
    private function assertResponseResource(ClientResponse $response)
    {
        $resource = $response->getDocument()->getResource();
        $this->assertInstanceOf(ResourceObjectInterface::class, $resource);
        $this->assertSame('posts', $resource->getType());
        $this->assertSame('1', $resource->getId());
    }
}
