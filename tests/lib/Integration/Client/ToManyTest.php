<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Encoder\Parameters\EncodingParameters;
use DummyApp\Post;
use DummyApp\Tag;

class ToManyTest extends TestCase
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

    public function testAddTo()
    {
        $tags = factory(Tag::class, 2)->create();

        $expected = $this->willSeeIdentifiers($tags);
        $actual = $this->client->addToRecordRelationship($this->post, $tags, 'tags');

        $data = [
            ['type' => 'tags', 'id' => $tags->first()->getRouteKey()],
            ['type' => 'tags', 'id' => $tags->last()->getRouteKey()],
        ];

        $this->assertSame($expected, $actual);
        $this->assertRequested('POST', "/posts/{$this->post->getRouteKey()}/relationships/tags");
        $this->assertSentDocument(compact('data'));
    }

    public function testRemoveFrom()
    {
        $tags = factory(Tag::class, 2)->create();

        $expected = $this->willSeeIdentifiers($tags);
        $actual = $this->client->removeFromRecordRelationship($this->post, $tags, 'tags');

        $data = [
            ['type' => 'tags', 'id' => $tags->first()->getRouteKey()],
            ['type' => 'tags', 'id' => $tags->last()->getRouteKey()],
        ];

        $this->assertSame($expected, $actual);
        $this->assertRequested('DELETE', "/posts/{$this->post->getRouteKey()}/relationships/tags");
        $this->assertSentDocument(compact('data'));
    }
}
