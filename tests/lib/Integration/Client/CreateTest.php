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

class CreateTest extends TestCase
{

    public function test()
    {
        $post = factory(Post::class)->make();

        $resource = [
            'type' => 'posts',
            'attributes' => [
                'createdAt' => null,
                'content' => $post->content,
                'deletedAt' => null,
                'published' => $post->published_at,
                'slug' => $post->slug,
                'title' => $post->title,
                'updatedAt' => null,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $post->author_id,
                    ],
                ],
            ],
        ];

        $expected = $this->willSeeResource($post, 201);
        $actual = $this->client->withIncludePaths('author')->createRecord($post);

        $this->assertSame($expected, $actual, 'http response');
        $this->assertRequested('POST', '/posts');
        $this->assertHeader('Accept', 'application/vnd.api+json');
        $this->assertHeader('Content-Type', 'application/vnd.api+json');
        $this->assertSentDocument(['data' => $resource]);
    }

    public function testNullRelation()
    {
        $post = factory(Post::class)->make(['author_id' => null]);

        $resource = [
            'type' => 'posts',
            'attributes' => [
                'createdAt' => null,
                'updatedAt' => null,
                'deletedAt' => null,
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
                'published' => $post->published_at,
            ],
            'relationships' => [
                'author' => [
                    'data' => null,
                ],
            ],
        ];

        $this->willSeeResource($post, 201);
        $this->client->withIncludePaths('author')->createRecord($post);

        $this->assertSentDocument(['data' => $resource]);
    }

    public function testWithCompoundDocumentAndFieldsets()
    {
        $this->mock->append();
        $post = factory(Post::class)->make();

        $document = [
            'data' => [
                'type' => 'posts',
                'attributes' => [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'published' => $post->published_at,
                ],
                'relationships' => [
                    'author' => [
                        'data' => [
                            'type' => 'users',
                            'id' => (string) $post->author->getRouteKey(),
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'type' => 'users',
                    'id' => (string) $post->author->getRouteKey(),
                    'attributes' => [
                        'name' => $post->author->name,
                    ],
                ]
            ],
        ];

        $expected = $this->willSeeResource($post, 201);
        $actual = $this->client
            ->withIncludePaths('author')
            ->withCompoundDocuments()
            ->withFields('posts', ['title', 'slug', 'content', 'published', 'author'])
            ->withFields('users', 'name')
            ->createRecord($post);

        $this->assertSame($expected, $actual, 'http response');
        $this->assertSentDocument($document);
    }

    /**
     * As the resource does not have an id, we expect links to be removed even if the
     * client is set to include links.
     */
    public function testRemovesLinksIfNoId()
    {
        $this->mock->append();
        $post = factory(Post::class)->make();
        $userId = (string) $post->author->getRouteKey();

        $document = [
            'data' => [
                'type' => 'posts',
                'attributes' => [
                    'createdAt' => null,
                    'updatedAt' => null,
                    'deletedAt' => null,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'published' => $post->published_at,
                ],
                'relationships' => [
                    'author' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $userId,
                        ],
                    ],
                ],
            ],
        ];

        $params = new EncodingParameters(['author', 'comments'], ['users' => ['name', 'email']]);

        $expected = $this->willSeeResource($post, 201);
        $actual = $this->client
            ->withLinks()
            ->withIncludePaths('author')
            ->createRecord($post, $params);

        $this->assertSame($expected, $actual, 'http response');
        $this->assertQueryParameters([
            'include' => 'author,comments',
            'fields[users]' => 'name,email',
        ]);
        $this->assertSentDocument($document);
    }

    public function testWithClientIdAndLinks()
    {
        $this->mock->append();
        $post = factory(Post::class)->create();
        $self = "http://localhost/api/v1/posts/{$post->getRouteKey()}";

        $document = [
            'data' => [
                'type' => 'posts',
                'id' => (string) $post->getRouteKey(),
                'attributes' => [
                    'createdAt' => $post->created_at->toJSON(),
                    'updatedAt' => $post->updated_at->toJSON(),
                    'deletedAt' => null,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => $post->content,
                    'published' => $post->published_at,
                ],
                'relationships' => [
                    'author' => [
                        'data' => [
                            'type' => 'users',
                            'id' => (string) $post->author->getRouteKey(),
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
            ],
        ];

        $expected = $this->willSeeResponse(null, 204);
        $actual = $this->client
            ->withLinks()
            ->withIncludePaths('author')
            ->createRecord($post);

        $this->assertSame($expected, $actual, 'http response');
        $this->assertSentDocument($document);
    }

    public function testWithParameters()
    {
        $post = factory(Post::class)->make();

        $this->willSeeResource($post, 201);
        $this->client->createRecord($post, [
            'include' => 'author,site',
            'fields' => [
                'author' => 'first-name,surname',
                'site' => 'uri',
            ],
            'foo' => 'bar',
        ]);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'fields[author]' => 'first-name,surname',
            'fields[site]' => 'uri',
            'foo' => 'bar'
        ]);
    }

    public function testWithEncodingParameters()
    {
        $parameters = new EncodingParameters(
            ['author', 'site'],
            ['author' => ['first-name', 'surname'], 'site' => ['uri']],
            null,
            null,
            null,
            ['foo' => 'bar']
        );

        $post = factory(Post::class)->make();

        $this->willSeeResource($post, 201);
        $this->client->createRecord($post, $parameters);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'fields[author]' => 'first-name,surname',
            'fields[site]' => 'uri',
            'foo' => 'bar'
        ]);
    }

    public function testWithOptions()
    {
        $post = factory(Post::class)->make();

        $this->willSeeResource($post, 201);

        $options = [
            'headers' => [
                'X-Foo' => 'Bar',
                'Content-Type' => 'text/html', // should be overwritten
            ],
        ];

        $this->client
            ->withOptions($options)
            ->createRecord($post);

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Content-Type', 'application/vnd.api+json');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testError()
    {
        $post = factory(Post::class)->make();

        $this->willSeeErrors([], 405);
        $this->expectException(ClientException::class);
        $this->client->createRecord($post);
    }

}
