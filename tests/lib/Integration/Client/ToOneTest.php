<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Encoder\Parameters\EncodingParameters;
use DummyApp\Post;

class ToOneTest extends TestCase
{

    /**
     * @var Post
     */
    private $post;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->post = factory(Post::class)->create();
    }

    public function testRelated()
    {
        $expected = $this->willSeeResource($this->post->author);
        $actual = $this->client->readRecordRelated($this->post, 'author');

        $this->assertSame($expected, $actual);
        $this->assertRequested('GET', "/posts/{$this->post->getRouteKey()}/author");
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testRelatedWithParameters()
    {
        $parameters = new EncodingParameters(
            ['posts'],
            ['author' => ['first-name', 'surname']]
        );

        $this->willSeeResource($this->post->author);
        $this->client->readRecordRelated($this->post, 'author', $parameters);

        $this->assertQueryParameters([
            'include' => 'posts',
            'fields[author]' => 'first-name,surname',
        ]);
    }

    public function testRelationship()
    {
        $expected = $this->willSeeIdentifiers($this->post->author);
        $actual = $this->client->readRecordRelationship($this->post, 'author');

        $this->assertSame($expected, $actual);
        $this->assertRequested('GET', "/posts/{$this->post->getRouteKey()}/relationships/author");
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testRelationshipWithParameters()
    {
        $parameters = new EncodingParameters(
            ['posts'],
            ['author' => ['first-name', 'surname']]
        );

        $this->willSeeIdentifiers($this->post->author);
        $this->client->readRecordRelationship($this->post, 'author', $parameters);

        $this->assertQueryParameters([
            'include' => 'posts',
            'fields[author]' => 'first-name,surname',
        ]);
    }

    public function testReplace()
    {
        $expected = $this->willSeeIdentifiers($this->post->author);
        $actual = $this->client->replaceRecordRelationship($this->post, $this->post->author, 'author');

        $data = [
            'type' => 'users',
            'id' => (string) $this->post->author_id,
        ];

        $this->assertSame($expected, $actual);
        $this->assertRequested('PATCH', "/posts/{$this->post->getRouteKey()}/relationships/author");
        $this->assertSentDocument(compact('data'));
    }
}
