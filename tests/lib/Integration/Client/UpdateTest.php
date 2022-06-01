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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Encoder\Parameters\EncodingParameters;
use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use DummyApp\Post;

class UpdateTest extends TestCase
{

    /**
     * @var Post
     */
    private $post;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->post = factory(Post::class)->create();
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
    public function test()
    {
        $resource = [
            'type' => 'posts',
            'id' => (string) $this->post->getRouteKey(),
            'attributes' => [
                'createdAt' => $this->post->created_at->toJSON(),
                'updatedAt' => $this->post->updated_at->toJSON(),
                'deletedAt' => null,
                'title' => $this->post->title,
                'slug' => $this->post->slug,
                'content' => $this->post->content,
                'published' => $this->post->published_at,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $this->post->author_id,
                    ],
                ],
            ],
        ];

        $expected = $this->willSeeResource($this->post);
        $actual = $this->client->withIncludePaths('author')->updateRecord($this->post);

        $this->assertSame($expected, $actual, 'http response');
        $this->assertRequested('PATCH', "/posts/{$this->post->getRouteKey()}");
        $this->assertHeader('Accept', 'application/vnd.api+json');
        $this->assertHeader('Content-Type', 'application/vnd.api+json');
        $this->assertSentDocument(['data' => $resource]);
    }

    /**
     * Test that we can set the client to send both links and included resources.
     * We still need to strip out any relationships that do not have data
     * because these are not allowed by the spec.
     */
    public function testWithLinksAndIncluded()
    {
        $self = "http://localhost/api/v1/posts/{$this->post->getRouteKey()}";

        $resource = [
            'type' => 'posts',
            'id' => (string) $this->post->getRouteKey(),
            'attributes' => [
                'createdAt' => $this->post->created_at->toJSON(),
                'updatedAt' => $this->post->updated_at->toJSON(),
                'deletedAt' => null,
                'title' => $this->post->title,
                'slug' => $this->post->slug,
                'content' => $this->post->content,
                'published' => $this->post->published_at,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $this->post->author_id,
                    ],
                    'links' => [
                        'self' => "{$self}/relationships/author",
                        'related' => "{$self}/author",
                    ],
                ],
            ],
            'links' => [
                'self' => $self,
            ],
        ];

        $self = "http://localhost/api/v1/users/{$this->post->author->getRouteKey()}";

        $author = [
            'type' => 'users',
            'id' => (string) $this->post->author->getRouteKey(),
            'attributes' => [
                'createdAt' => $this->post->author->created_at->toJSON(),
                'updatedAt' => $this->post->author->updated_at->toJSON(),
                'name' => $this->post->author->name,
                'email' => $this->post->author->email,
            ],
            'relationships' => [
                'phone' => [
                    'links' => [
                        'self' => "{$self}/relationships/phone",
                        'related' => "{$self}/phone",
                    ],
                ],
                'roles' => [
                    'links' => [
                        'self' => "{$self}/relationships/roles",
                        'related' => "{$self}/roles",
                    ],
                ],
            ],
            'links' => [
                'self' => 'http://localhost/api/v1/users/1',
            ],
        ];

        $this->willSeeResource($this->post);

        $this->client
            ->withIncludePaths('author')
            ->withCompoundDocuments()
            ->withLinks()
            ->updateRecord($this->post);

        $this->assertSentDocument([
            'data' => $resource,
            'included' => [$author],
        ]);
    }

    /**
     * Test that we can set the client to send both links and included resources.
     * We still need to strip out any relationships that do not have data
     * because these are not allowed by the spec.
     */
    public function testWithIncludedAndWithoutLinks()
    {
        $resource = [
            'type' => 'posts',
            'id' => (string) $this->post->getRouteKey(),
            'attributes' => [
                'createdAt' => $this->post->created_at->toJSON(),
                'updatedAt' => $this->post->updated_at->toJSON(),
                'deletedAt' => null,
                'title' => $this->post->title,
                'slug' => $this->post->slug,
                'content' => $this->post->content,
                'published' => $this->post->published_at,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $this->post->author_id,
                    ],
                ],
            ],
        ];

        $author = [
            'type' => 'users',
            'id' => (string) $this->post->author->getRouteKey(),
            'attributes' => [
                'createdAt' => $this->post->author->created_at->toJSON(),
                'updatedAt' => $this->post->author->updated_at->toJSON(),
                'name' => $this->post->author->name,
                'email' => $this->post->author->email,
            ],
        ];

        $this->willSeeResource($this->post);

        $this->client
            ->withIncludePaths('author')
            ->withCompoundDocuments()
            ->updateRecord($this->post);

        $this->assertSentDocument([
            'data' => $resource,
            'included' => [$author],
        ]);
    }

    public function testWithFieldsets()
    {
        $resource = [
            'type' => 'posts',
            'id' => (string) $this->post->getRouteKey(),
            'attributes' => [
                'content' => $this->post->content,
                'published' => $this->post->published_at,
            ],
        ];

        $this->willSeeResource($this->post);

        $this->client
            ->withFields('posts', ['content', 'published'])
            ->updateRecord($this->post);

        $this->assertSentDocument(['data' => $resource]);
    }

    public function testWithParameters()
    {
        $this->willSeeResource($this->post);

        $this->client->updateRecord($this->post, [
            'include' => 'author,site',
        ]);

        $this->assertQueryParameters([
            'include' => 'author,site',
        ]);
    }

    public function testWithEncodingParameters()
    {
        $parameters = new EncodingParameters(
            ['author', 'site'],
            ['author' => ['first-name', 'surname'], 'site' => ['uri']]
        );

        $this->willSeeResource($this->post);
        $this->client->updateRecord($this->post, $parameters);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'fields[author]' => 'first-name,surname',
            'fields[site]' => 'uri',
        ]);
    }

    public function testWithNoContentResponse()
    {
        $expected = $this->willSeeResponse(null, 204);
        $response = $this->client->updateRecord($this->post);
        $this->assertSame($expected, $response, 'response');
    }

    public function testWithOptions()
    {
        $options = [
            'headers' => [
                'X-Foo' => 'Bar',
            ],
        ];

        $this->willSeeResource($this->post);
        $this->client->withOptions($options)->updateRecord($this->post);

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    public function testError()
    {
        $this->willSeeErrors([], 422);
        $this->expectException(ClientException::class);
        $this->client->updateRecord($this->post);
    }
}
