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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Document;

use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;

class ResourceObjectTest extends TestCase
{

    /**
     * @var array
     */
    private $values;

    /**
     * @var ResourceObject
     */
    private $resource;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->values = [
            'type' => 'posts',
            'id' => '1',
            'attributes' => [
                'title' => 'Hello World',
                'content' => '...',
                'published' => null,
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
        
        $this->resource = ResourceObject::create($this->values);
    }

    public function testFields(): array
    {
        $expected = [
            'author' => [
                'type' => 'users',
                'id' => '123',
            ],
            'content' => '...',
            'id' => '1',
            'published' => null,
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

        $this->assertSame($expected, $this->resource->all(), 'all');
        $this->assertSame($expected, iterator_to_array($this->resource), 'iterator');

        $this->assertSame($fields = [
            'author',
            'comments', // we expect comments to be included even though it has no data.
            'content',
            'id',
            'published',
            'tags',
            'title',
            'type',
        ], $this->resource->fields()->all(), 'fields');

        $this->assertTrue($this->resource->has(...$fields), 'has all fields');
        $this->assertFalse($this->resource->has('title', 'foobar'), 'does not have field');

        return $expected;
    }

    /**
     * @param array $expected
     * @depends testFields
     */
    public function testGetValue(array $expected): void
    {
        foreach ($expected as $field => $value) {
            $this->assertTrue(isset($this->resource[$field]), "$field exists as array");
            $this->assertTrue(isset($this->resource[$field]), "$field exists as object");
            $this->assertSame($value, $this->resource[$field], "$field array value");
            $this->assertSame($value, $this->resource->{$field}, "$field object value");
            $this->assertSame($value, $this->resource->get($field), "$field get value");
        }

        $this->assertFalse(isset($this->resource['foo']), 'foo does not exist');
    }

    public function testGetWithDotNotation(): void
    {
        $this->assertSame('123', $this->resource->get('author.id'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertSame('123', $this->resource->get('author.id', true));
        $this->assertNull($this->resource->get('published', true));
        $this->assertTrue($this->resource->get('foobar', true));
    }

    public function testCannotSetOffset(): void
    {
        $this->expectException(\LogicException::class);
        $this->resource['foo'] = 'bar';
    }

    public function testCannotUnsetOffset(): void
    {
        $this->expectException(\LogicException::class);
        unset($this->resource['content']);
    }

    public function testCannotUnset(): void
    {
        $this->expectException(\LogicException::class);
        unset($this->resource->content);
    }

    public function testCannotSet(): void
    {
        $this->expectException(\LogicException::class);
        $this->resource['foo'] = 'bar';
    }

    /**
     * @return array
     */
    public function pointerProvider(): array
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
     * @param string $key
     * @param string $expected
     * @dataProvider pointerProvider
     */
    public function testPointer(string $key, string $expected): void
    {
        $this->assertSame($expected, $this->resource->pointer($key));
    }

    public function testForget(): void
    {
        $expected = $this->values;
        unset($expected['attributes']['content']);
        unset($expected['relationships']['comments']);

        $this->assertNotSame($this->resource, $actual = $this->resource->forget('content', 'comments'));
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testOnly(): void
    {
        $expected = [
            'type' => $this->values['type'],
            'id' => $this->values['id'],
            'attributes' => [
                'content' => $this->values['attributes']['content'],
            ],
            'relationships' => [
                'comments' => $this->values['relationships']['comments'],
            ],
        ];

        $this->assertNotSame($this->resource, $actual = $this->resource->only('content', 'comments'));
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testReplaceAttribute(): void
    {
        $expected = $this->values;
        $expected['attributes']['content'] = 'My first post.';

        $this->assertNotSame($this->resource, $actual = $this->resource->replace('content', 'My first post.'));
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testReplaceRelationship(): void
    {
        $comments = [
            ['type' => 'comments', 'id' => '123456'],
        ];

        $expected = $this->values;
        $expected['relationships']['comments']['data'] = $comments;

        $this->assertNotSame($this->resource, $actual = $this->resource->replace('comments', $comments));
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithType(): void
    {
        $expected = $this->values;
        $expected['type'] = 'foobar';

        $this->assertNotSame($this->resource, $actual = $this->resource->withType('foobar'));
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithoutId(): void
    {
        $expected = $this->values;
        unset($expected['id']);

        $this->assertNotSame($this->resource, $actual = $this->resource->withoutId());
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithId(): void
    {
        $expected = $this->values;
        $expected['id'] = '99';

        $this->assertNotSame($this->resource, $actual = $this->resource->withId('99'));
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithAttributes(): void
    {
        $expected = $this->values;
        $expected['attributes'] = ['foo' => 'bar'];

        $this->assertNotSame($this->resource, $actual = $this->resource->withAttributes($expected['attributes']));
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithoutAttributes(): void
    {
        $expected = $this->values;
        unset($expected['attributes']);

        $this->assertNotSame($this->resource, $actual = $this->resource->withoutAttributes());
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithRelationships(): void
    {
        $expected = $this->values;
        $expected['relationships'] = [
            'foo' => ['data' => ['type' => 'foos', 'id' => 'bar']]
        ];

        $this->assertNotSame($this->resource, $actual = $this->resource->withRelationships($expected['relationships']));
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testWithoutRelationships(): void
    {
        $expected = $this->values;
        unset($expected['relationships']);

        $this->assertNotSame($this->resource, $actual = $this->resource->withoutRelationships());
        $this->assertSame($this->values, $this->resource->toArray(), 'original resource is not modified');
        $this->assertSame($expected, $actual->toArray());
    }

    public function testJsonSerialize(): void
    {
        $this->assertJsonStringEqualsJsonString(
            json_encode($this->values),
            json_encode($this->resource)
        );
    }
}
