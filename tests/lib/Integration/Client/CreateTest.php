<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use DummyApp\Post;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

class CreateTest extends TestCase
{

    public function test()
    {
        $post = factory(Post::class)->make();

        $resource = [
            'type' => 'posts',
            'attributes' => [
                'created-at' => null,
                'updated-at' => null,
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
                'published' => $post->published_at,
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
                    'created-at' => null,
                    'updated-at' => null,
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
                    'created-at' => $post->created_at->toAtomString(),
                    'updated-at' => $post->updated_at->toAtomString(),
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
