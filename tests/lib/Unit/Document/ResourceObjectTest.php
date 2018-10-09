<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Document;

use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;

class ResourceObjectTest extends TestCase
{

    /**
     * @var array
     */
    private $resource;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->resource = [
            'type' => 'posts',
            'id' => '1',
            'attributes' => [
                'title' => 'Hello World',
                'content' => '...',
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => '123',
                    ],
                ],
                'tags' => [
                    'data' => [
                        [
                            'type' => 'tags',
                            'id' => '4',
                        ],
                        [
                            'type' => 'tags',
                            'id' => '5',
                        ],
                    ],
                ],
                'comments' => [
                    'links' => [
                        'related' => '/api/posts/1/comments',
                    ],
                ],
            ],
        ];
    }

    public function testFields()
    {
        $expected = [
            'author' => [
                'type' => 'users',
                'id' => '123',
            ],
            'content' => '...',
            'id' => '1',
            'tags' => [
                [
                    'type' => 'tags',
                    'id' => '4',
                ],
                [
                    'type' => 'tags',
                    'id' => '5',
                ],
            ],
            'title' => 'Hello World',
            'type' => 'posts',
        ];

        $resource = ResourceObject::create($this->resource);

        $this->assertSame($expected, $resource->all(), 'all');
        $this->assertSame($expected, iterator_to_array($resource), 'iterator');

        $this->assertSame($fields = [
            'author',
            'comments', // we expect comments to be included even though it has no data.
            'content',
            'id',
            'tags',
            'title',
            'type',
        ], $resource->fields()->all(), 'fields');

        $this->assertTrue($resource->has(...$fields), 'has all fields');
        $this->assertFalse($resource->has('title', 'foobar'), 'does not have field');
    }

    /**
     * @return array
     */
    public function pointerProvider()
    {
        return [
            ['type', '/data/type'],
            ['id', '/data/id'],
            ['title', '/data/attributes/title'],
            ['title.foo.bar', '/data/attributes/title/foo/bar'],
            ['author', '/data/relationships/author'],
            ['author.type', '/data/relationships/author/data/type'],
            ['tags.0.id', '/data/relationships/tags/data/0/id'],
            ['comments', '/data/relationships/comments'],
            ['foo', '/data'],
        ];
    }

    /**
     * @param $key
     * @param $expected
     * @dataProvider pointerProvider
     */
    public function testPointer($key, $expected)
    {
        $resource = ResourceObject::create($this->resource);

        $this->assertSame($expected, $resource->pointer($key));
    }

    public function testForget()
    {
        $expected = $this->resource;
        unset($expected['attributes']['content']);
        unset($expected['relationships']['comments']);

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->forget('content', 'comments'));
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testOnly()
    {
        $expected = [
            'type' => $this->resource['type'],
            'id' => $this->resource['id'],
            'attributes' => [
                'content' => $this->resource['attributes']['content'],
            ],
            'relationships' => [
                'comments' => $this->resource['relationships']['comments'],
            ],
        ];

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->only('content', 'comments'));
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testReplaceAttribute()
    {
        $expected = $this->resource;
        $expected['attributes']['content'] = 'My first post.';

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->replace('content', 'My first post.'));
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testReplaceRelationship()
    {
        $comments = [
            ['type' => 'comments', 'id' => '123456'],
        ];

        $expected = $this->resource;
        $expected['relationships']['comments']['data'] = $comments;

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->replace('comments', $comments));
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithType()
    {
        $expected = $this->resource;
        $expected['type'] = 'foobar';

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->withType('foobar'));
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithoutId()
    {
        $expected = $this->resource;
        unset($expected['id']);

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->withoutId());
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithId()
    {
        $expected = $this->resource;
        $expected['id'] = '99';

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->withId('99'));
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithAttributes()
    {
        $expected = $this->resource;
        $expected['attributes'] = ['foo' => 'bar'];

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->withAttributes($expected['attributes']));
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithoutAttributes()
    {
        $expected = $this->resource;
        unset($expected['attributes']);

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->withoutAttributes());
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithRelationships()
    {
        $expected = $this->resource;
        $expected['relationships'] = [
            'foo' => ['data' => ['type' => 'foos', 'id' => 'bar']]
        ];

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->withRelationships($expected['relationships']));
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithoutRelationships()
    {
        $expected = $this->resource;
        unset($expected['relationships']);

        $resource = ResourceObject::create($this->resource);

        $this->assertNotSame($resource, $actual = $resource->withoutRelationships());
        $this->assertSame($this->resource, $resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }
}
