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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Http\Client;

use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Document\Error;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Client\ClientSerializer;
use CloudCreativity\LaravelJsonApi\Http\Client\GuzzleClient;
use CloudCreativity\LaravelJsonApi\Http\Responses\Response as ClientResponse;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Container\Container;
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
 * @package CloudCreativity\LaravelJsonApi
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
     * @var GuzzleClient
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

        $serializer = $this->encoder = $this->createMock(SerializerInterface::class);
        $serializer->method('withMeta')->willReturnSelf();
        $serializer->method('withLinks')->willReturnSelf();

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
        $factory = new Factory($this->createMock(Container::class));
        $this->client = new GuzzleClient(
            $factory,
            $http,
            $container,
            new ClientSerializer($serializer, $factory)
        );
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
        $expected = (array) $this->record;
        unset($expected['id']);

        $this->record->id = null;
        $this->willSerializeRecord()->willSeeRecord(201);
        $response = $this->client->create($this->record);

        $this->assertSame(201, $response->getPsrResponse()->getStatusCode());
        $this->assertResponseResource($response);
        $this->assertRequested('POST', '/posts');
        $this->assertRequestSentRecord(['data' => $expected]);
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

    /**
     * By default when updating a record we expect:
     *
     * - Any relationship without a `data` key to be removed.
     * - Links to be removed from relationships.
     * - Links to be removed from the resource.
     * - Included resources to be removed.
     *
     * This is because the JSON API spec states that all relationships that are sent
     * for an update request MUST contain a data key.
     *
     * For links, we should not send them by default because if we use our JSON API
     * config for an external API, the links refer to that external API not our
     * server.
     */
    public function testUpdate()
    {
        $serialized = (array) $this->record;
        $serialized['links'] = ['self' => '/api/v1/posts/1'];
        $serialized['relationships'] = [
            'author' => [
                'data' => [
                    'type' => 'users',
                    'id' => '123',
                ],
                'links' => [
                    'self' => '/api/v1/posts/1/relationships/author',
                ],
            ],
            'comments' => [
                'links' => [
                    'self' => '/api/v1/posts/1/relationships/comments',
                ],
            ],
        ];

        $expected = $serialized;
        unset($expected['links']);
        unset($expected['relationships']['comments']);
        unset($expected['relationships']['author']['links']);

        $document = [
            'data' => $serialized,
            'included' => [
                [
                    'type' => 'users',
                    'id' => '123',
                    'attributes' => ['name' => 'John Doe'],
                ],
            ],
        ];

        $this->willSerializeRecord(null, $document)->willSeeRecord();
        $response = $this->client->update($this->record);

        $this->assertSame(200, $response->getPsrResponse()->getStatusCode());
        $this->assertResponseResource($response);
        $this->assertRequested('PATCH', '/posts/1');
        $this->assertRequestSentRecord(['data' => $expected]);
        $this->assertHeader('Accept', 'application/vnd.api+json');
        $this->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Test that we can set the client to send both links and included resources.
     * We still need to strip out any relationships that do not have data
     * because these are not allowed by the spec.
     */
    public function testUpdateWithLinksAndIncluded()
    {
        $serialized = (array) $this->record;
        $serialized['links'] = ['self' => '/api/v1/posts/1'];
        $serialized['relationships'] = [
            'author' => [
                'data' => [
                    'type' => 'users',
                    'id' => '123',
                ],
                'links' => [
                    'self' => '/api/v1/posts/1/relationships/author',
                ],
            ],
            'comments' => [
                'links' => [
                    'self' => '/api/v1/posts/1/relationships/comments',
                ],
            ],
        ];

        $expected = $serialized;
        unset($expected['relationships']['comments']);

        $document = [
            'data' => $serialized,
            'included' => [
                [
                    'type' => 'users',
                    'id' => '123',
                    'attributes' => ['name' => 'John Doe'],
                    'relationships' => [
                        'posts' => [
                            'links' => [
                                'self' => '/api/v1/users/123/posts',
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => '/api/v1/users/123',
                    ],
                ],
            ],
        ];

        $this->willSerializeRecord(new EncodingParameters(['author']), $document)
            ->willSeeRecord();

        $this->client
            ->withIncludePaths('author')
            ->withCompoundDocuments()
            ->withLinks()
            ->update($this->record);

        $this->assertRequestSentRecord([
            'data' => $expected,
            'included' => $document['included'],
        ]);
    }

    /**
     * Test that we can set the client to send both links and included resources.
     * We still need to strip out any relationships that do not have data
     * because these are not allowed by the spec.
     */
    public function testUpdateWithIncludedAndWithoutLinks()
    {
        $serialized = (array) $this->record;
        $serialized['links'] = ['self' => '/api/v1/posts/1'];
        $serialized['relationships'] = [
            'author' => [
                'data' => [
                    'type' => 'users',
                    'id' => '123',
                ],
                'links' => [
                    'self' => '/api/v1/posts/1/relationships/author',
                ],
            ],
            'comments' => [
                'links' => [
                    'self' => '/api/v1/posts/1/relationships/comments',
                ],
            ],
        ];

        $document = [
            'data' => $serialized,
            'included' => [
                [
                    'type' => 'users',
                    'id' => '123',
                    'attributes' => ['name' => 'John Doe'],
                    'relationships' => [
                        'posts' => [
                            'links' => [
                                'self' => '/api/v1/users/123/posts',
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => '/api/v1/users/123',
                    ],
                ],
            ],
        ];

        $expected = $document;

        unset(
            $expected['data']['links'],
            $expected['data']['relationships']['author']['links'],
            $expected['data']['relationships']['comments'],
            $expected['included'][0]['links'],
            $expected['included'][0]['relationships'] // as relationships are now empty, do not include them
        );

        $this->willSerializeRecord(new EncodingParameters(['author']), $document)
            ->willSeeRecord();

        $this->client
            ->withIncludePaths('author')
            ->withCompoundDocuments()
            ->update($this->record);

        $this->assertRequestSentRecord($expected);
    }

    public function testUpdateWithFieldsets()
    {
        $expected = new EncodingParameters(
            null,
            ['posts' => ['content', 'published-at']]
        );

        $this->willSerializeRecord($expected)->willSeeRecord();
        $client = $this->client->withFields('posts', 'content', 'published-at');

        $this->assertNotSame($this->client, $client, 'client field sets are immutable');
        $client->update($this->record);
    }

    public function testUpdateWithIncludePaths()
    {
        $expected = new EncodingParameters(['author']);

        $this->willSerializeRecord($expected)->willSeeRecord();
        $client = $this->client->withIncludePaths('author');

        $this->assertNotSame($this->client, $client, 'client include paths are immutable');
        $client->update($this->record);
    }

    public function testUpdateWithParameters()
    {
        $parameters = new EncodingParameters(
            ['author', 'site'],
            ['author' => ['first-name', 'surname'], 'site' => ['uri']]
        );

        $this->willSerializeRecord()->willSeeRecord(201);
        $this->client->update($this->record, $parameters);

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
        $this->client->update($this->record, null, [
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

        $this->willSeeErrors([
            'errors' => [
                [
                    'id' => $expected->getId(),
                    'links' => [
                        'about' => 'http://localhost/errors/server',
                    ],
                    'status' => $expected->getStatus(),
                    'code' => $expected->getCode(),
                    'title' => $expected->getTitle(),
                    'detail' => $expected->getDetail(),
                    'source' => [
                        'pointer' => '/',
                    ],
                    'meta' => [
                        'foo' => 'bar',
                    ],
                ],
            ]
        ], 500);

        try {
            $this->client->delete($this->record);
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertEquals([$expected], $ex->getErrors()->getArrayCopy());
        }
    }

    /**
     * @param EncodingParametersInterface|null $parameters
     * @param array|null $serialized
     * @return $this
     */
    private function willSerializeRecord(EncodingParametersInterface $parameters = null, array $serialized = null)
    {
        $this->encoder
            ->expects($this->once())
            ->method('serializeData')
            ->with($this->record, $parameters ?: new EncodingParameters())
            ->willReturn($serialized ?: ['data' => (array) $this->record]);

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
     * @param mixed|null $expected
     * @return void
     */
    private function assertRequestSentRecord($expected = null)
    {
        $expected = json_encode($expected ?: ['data' => (array) $this->record]);
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
