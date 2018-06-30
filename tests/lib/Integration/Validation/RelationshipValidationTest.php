<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Validation;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Post;

class RelationshipValidationTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @return array
     */
    public function toOneProvider()
    {
        return [
            'data:required' => [
                new \stdClass(),
                [
                    'title' => 'Required Member',
                    'detail' => "The member 'data' is required.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data:not object' => [
                ['data' => false],
                [
                    'title' => 'Relationship Expected',
                    'detail' => "The member 'data' must be a relationship object.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.type:required' => [
                [
                    'data' => [
                        'id' => '1',
                    ],
                ],
                [
                    'title' => 'Required Member',
                    'detail' => "The member 'type' is required.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.type:not string' => [
                [
                    'data' => [
                        'type' => null,
                        'id' => '1',
                    ],
                ],
                [
                    'title' => 'String Expected',
                    'detail' => "The member 'type' must be a string.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/type
                ],
            ],
            'data.type:empty' => [
                [
                    'data' => [
                        'type' => '',
                        'id' => '1',
                    ],
                ],
                [
                    'title' => 'Value Expected',
                    'detail' => "The member 'type' cannot be empty.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/type
                ],
            ],
            'data.type:not supported' => [
                [
                    'data' => [
                        'type' => 'tags',
                        'id' => '1',
                    ],
                ],
                [
                    'title' => 'Invalid Relationship',
                    'detail' => "Resource 'tags' is not among the type(s) supported by this relationship. Expecting only 'users' resources.",
                    'status' => 400,
                    'source' => ['pointer' => '/data/type'],
                ],
            ],
            'data.type:not recognised' => [
                [
                    'data' => [
                        'type' => 'foobar',
                        'id' => '1',
                    ],
                ],
                [
                    'title' => 'Invalid Relationship',
                    'detail' => "Resource type 'foobar' is not recognised.",
                    'status' => 400,
                    'source' => ['pointer' => '/data/type'],
                ],
            ],
            'data.id:required' => [
                [
                    'data' => [
                        'type' => 'users',
                    ],
                ],
                [
                    'title' => 'Required Member',
                    'detail' => "The member 'id' is required.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.id:not string' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => null,
                    ],
                ],
                [
                    'title' => 'String Expected',
                    'detail' => "The member 'id' must be a string.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/id
                ],
            ],
            'data.id:integer' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => 1,
                    ],
                ],
                [
                    'title' => 'String Expected',
                    'detail' => "The member 'id' must be a string.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/id
                ],
            ],
            'data.id:empty' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => '',
                    ],
                ],
                [
                    'title' => 'Value Expected',
                    'detail' => "The member 'id' cannot be empty.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/id
                ],
            ],
            'data:does not exist' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => '99',
                    ],
                ],
                [
                    'title' => 'Invalid Relationship',
                    'detail' => 'The related resource does not exist.',
                    'status' => 404,
                    'source' => ['pointer' => '/data'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function toManyProvider()
    {
        return [
            'data:required' => [
                new \stdClass(),
                [
                    'title' => 'Required Member',
                    'detail' => "The member 'data' is required.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data:not object' => [
                ['data' => false],
                [
                    'title' => 'Relationship Expected',
                    'detail' => "The member 'data' must be a relationship object.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data.type:required' => [
                [
                    'data' => [
                        ['id' => '1'],
                    ],
                ],
                [
                    'title' => 'Required Member',
                    'detail' => "The member 'type' is required.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/0
                ],
            ],
            'data.type:not string' => [
                [
                    'data' => [
                        ['type' => null, 'id' => '1'],
                    ],
                ],
                [
                    'title' => 'String Expected',
                    'detail' => "The member 'type' must be a string.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/0/type
                ],
            ],
            'data.type:empty' => [
                [
                    'data' => [
                        ['type' => '', 'id' => '1'],
                    ],
                ],
                [
                    'title' => 'Value Expected',
                    'detail' => "The member 'type' cannot be empty.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/0/type
                ],
            ],
            'data.type:not supported' => [
                [
                    'data' => [
                        ['type' => 'users', 'id' => '1'],
                    ],
                ],
                [
                    'title' => 'Invalid Relationship',
                    'detail' => "Resource 'users' is not among the type(s) supported by this relationship. Expecting only 'tags' resources.",
                    'status' => 400,
                    'source' => ['pointer' => '/data/type'], // @todo should be /data/0/type
                ],
            ],
            'data.type:not recognised' => [
                [
                    'data' => [
                        ['type' => 'foobar', 'id' => '1'],
                    ],
                ],
                [
                    'title' => 'Invalid Relationship',
                    'detail' => "Resource type 'foobar' is not recognised.",
                    'status' => 400,
                    'source' => ['pointer' => '/data/type'], // @todo should be /data/0/type
                ],
            ],
            'data.id:required' => [
                [
                    'data' => [
                        ['type' => 'tags'],
                    ],
                ],
                [
                    'title' => 'Required Member',
                    'detail' => "The member 'id' is required.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/0
                ],
            ],
            'data.id:not string' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => null],
                    ],
                ],
                [
                    'title' => 'String Expected',
                    'detail' => "The member 'id' must be a string.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/0/id
                ],
            ],
            'data.id:integer' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => 1],
                    ],
                ],
                [
                    'title' => 'String Expected',
                    'detail' => "The member 'id' must be a string.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/0/id
                ],
            ],
            'data.id:empty' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => ''],
                    ],
                ],
                [
                    'title' => 'Value Expected',
                    'detail' => "The member 'id' cannot be empty.",
                    'status' => 400,
                    'source' => ['pointer' => '/data'], // @todo should be /data/0/id
                ],
            ],
            'data:does not exist' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => '99'],
                    ],
                ],
                [
                    'title' => 'Invalid Relationship',
                    'detail' => 'The related resource does not exist.',
                    'status' => 404,
                    'source' => ['pointer' => '/data'], // @todo should be /data/0
                ],
            ],
        ];
    }

    /**
     * @param $data
     * @param array $error
     * @dataProvider toOneProvider
     */
    public function testToOne($data, array $error)
    {
        $post = factory(Post::class)->create();

        $this->patchJsonApi("/api/v1/posts/{$post->getKey()}/relationships/author", json_encode($data))
            ->assertStatus($error['status'])
            ->assertJson([
                'errors' => [$error],
            ]);
    }

    /**
     * @param $data
     * @param array $error
     * @dataProvider toManyProvider
     */
    public function testToMany($data, array $error)
    {
        $post = factory(Post::class)->create();

        $this->patchJsonApi("/api/v1/posts/{$post->getKey()}/relationships/tags", json_encode($data))
            ->assertStatus($error['status'])
            ->assertJson([
                'errors' => [$error],
            ]);
    }
}
