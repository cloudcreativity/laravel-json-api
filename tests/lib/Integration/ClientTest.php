<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Client\ClientInterface;
use DummyApp\Post;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;
use function GuzzleHttp\Psr7\parse_query;

class ClientTest extends TestCase
{

    /**
     * @var HandlerStack
     */
    private $handler;

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
        parent::setUp();
        $this->handler = HandlerStack::create($this->mock = new MockHandler());
        $this->client = $this->api()->client('http://example.com', ['handler' => $this->handler]);
    }

    public function test()
    {
        $this->markTestIncomplete('@todo move other tests over from the unit test');
    }

    /**
     * Uses the host in the JSON API config file.
     */
    public function testClientWithoutHost()
    {
        $client = $this->api()->client(['handler' => $this->handler]);
        $post = factory(Post::class)->make();

        $this->willSeeResponse($post, 201);
        $client->create($post);

        $this->assertSame(
            'http://localhost/api/v1/posts',
            (string) $this->mock->getLastRequest()->getUri()
        );
    }

    /**
     * The host that is provided can have a path as well.
     */
    public function testClientWithHostAndPath()
    {
        $client = $this->api()->client(
            'http://example.com/foo',
            ['handler' => $this->handler]
        );

        $post = factory(Post::class)->make();

        $this->willSeeResponse($post, 201);
        $client->create($post);

        $this->assertSame(
            'http://example.com/foo/api/v1/posts',
            (string) $this->mock->getLastRequest()->getUri()
        );
    }

    /**
     * A Guzzle client can be provided.
     */
    public function testWithGuzzleClient()
    {
        $guzzle = new Client([
            'handler' => $this->handler,
            'base_uri' => 'http://foobar.local/baz/bat/',
        ]);

        $client = $this->api()->client($guzzle);
        $post = factory(Post::class)->make();

        $this->willSeeResponse($post, 201);
        $client->create($post);

        $this->assertSame(
            'http://foobar.local/baz/bat/posts',
            (string) $this->mock->getLastRequest()->getUri()
        );
    }

    public function testCreate()
    {
        $this->mock->append();
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

        $expected = $this->willSeeResponse($post, 201);
        $actual = $this->client->withIncludePaths('author')->create($post);

        $this->assertSame($expected, $actual->getPsrResponse(), 'http response');
        $this->assertRequested('POST', '/posts');
        $this->assertHeader('Accept', 'application/vnd.api+json');
        $this->assertHeader('Content-Type', 'application/vnd.api+json');
        $this->assertSentDocument(['data' => $resource]);
    }

    public function testCreateWithCompoundDocumentAndFieldsets()
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

        $expected = $this->willSeeResponse($post, 201);
        $actual = $this->client
            ->withIncludePaths('author')
            ->withCompoundDocuments()
            ->withFields('posts', 'title', 'slug', 'content', 'published', 'author')
            ->withFields('users', 'name')
            ->create($post);

        $this->assertSame($expected, $actual->getPsrResponse(), 'http response');
        $this->assertSentDocument($document);
    }

    /**
     * As the resource does not have an id, we expect links to be removed even if the
     * client is set to include links.
     */
    public function testCreateRemovesLinksIfNoId()
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

        $expected = $this->willSeeResponse($post, 201);
        $actual = $this->client
            ->withLinks()
            ->withIncludePaths('author')
            ->create($post, $params);

        $this->assertSame($expected, $actual->getPsrResponse(), 'http response');
        $this->assertQueryParameters([
            'include' => 'author,comments',
            'fields[users]' => 'name,email',
        ]);
        $this->assertSentDocument($document);
    }

    public function testCreateWithClientIdAndLinks()
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

        $expected = $this->willSeeResponse($post, 201);
        $actual = $this->client
            ->withLinks()
            ->withIncludePaths('author')
            ->create($post);

        $this->assertSame($expected, $actual->getPsrResponse(), 'http response');
        $this->assertSentDocument($document);
    }

    /**
     * @param mixed|null $json
     * @param int $status
     * @param array|null $headers
     * @return Response
     */
    private function willSeeResponse($json = null, $status = 200, array $headers = null)
    {
        if ($json instanceof Post) {
            $json = $this->api()->encoder()->serializeData($json);
        }

        if ($json) {
            $headers['Content-Type'] = 'application/vnd.api+json';
            $body = json_encode($json);
        } else {
            $body = null;
        }

        $this->mock->append(
            $response = new Response($status, $headers, $body)
        );

        return $response;
    }

    /**
     * @param array $expected
     */
    private function assertSentDocument(array $expected)
    {
        $this->assertJsonStringEqualsJsonString(
            json_encode($expected),
            (string) $this->mock->getLastRequest()->getBody()
        );
    }

    /**
     * @param $method
     * @param $path
     * @return void
     */
    private function assertRequested($method, $path)
    {
        $uri = 'http://example.com/api/v1' . $path;
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
}
