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

        $this->assertSame($expected, $resource->all());
        $this->assertSame($expected, iterator_to_array($resource));
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
}
