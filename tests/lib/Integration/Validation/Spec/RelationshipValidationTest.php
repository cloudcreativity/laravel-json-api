<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Validation\Spec;

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
    public static function toOneProvider()
    {
        return [
            'data:required' => [
                new \stdClass(),
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/'],
                ],
            ],
            'data:not object' => [
                ['data' => false],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data must be an object.",
                    'status' => '400',
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
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type is required.",
                    'status' => '400',
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
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/type'],
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
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type cannot be empty.",
                    'status' => '400',
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
                    'title' => 'Not Supported',
                    'detail' => "Resource type foobar is not recognised.",
                    'status' => '400',
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
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id is required.",
                    'status' => '400',
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
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/id'],
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
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/id'],
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
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/id'],
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
                    'title' => 'Not Found',
                    'detail' => 'The related resource does not exist.',
                    'status' => '404',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data:resource object with attributes' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => '1',
                        'attributes' => [
                            'name' => 'John Doe',
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => 'The member data must be a resource identifier.',
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
            'data:resource object with relationships' => [
                [
                    'data' => [
                        'type' => 'users',
                        'id' => '1',
                        'relationships' => [
                            'sites' => [
                                'data' => [],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => 'The member data must be a resource identifier.',
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function toManyProvider()
    {
        return [
            'data:required' => [
                new \stdClass(),
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/'],
                ],
            ],
            'data.type:required' => [
                [
                    'data' => [
                        ['id' => '1'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0'],
                ],
            ],
            'data.type:not string' => [
                [
                    'data' => [
                        ['type' => null, 'id' => '1'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/type'],
                ],
            ],
            'data.type:empty' => [
                [
                    'data' => [
                        ['type' => '', 'id' => '1'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/type'],
                ],
            ],
            'data.type:not recognised' => [
                [
                    'data' => [
                        ['type' => 'foobar', 'id' => '1'],
                    ],
                ],
                [
                    'title' => 'Not Supported',
                    'detail' => "Resource type foobar is not recognised.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/type'],
                ],
            ],
            'data.id:required' => [
                [
                    'data' => [
                        ['type' => 'tags'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0'],
                ],
            ],
            'data.id:not string' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => null],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/id'],
                ],
            ],
            'data.id:integer' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => 1],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id must be a string.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/id'],
                ],
            ],
            'data.id:empty' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => ''],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/0/id'],
                ],
            ],
            'data:does not exist' => [
                [
                    'data' => [
                        ['type' => 'tags', 'id' => '99'],
                    ],
                ],
                [
                    'title' => 'Not Found',
                    'detail' => 'The related resource does not exist.',
                    'status' => '404',
                    'source' => ['pointer' => '/data/0'],
                ],
            ],
            'data:resource object with attributes' => [
                [
                    'data' => [
                        [
                            'type' => 'tags',
                            'id' => '100',
                            'attributes' => [
                                'name' => 'News',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => 'The member 0 must be a resource identifier.',
                    'status' => '400',
                    'source' => ['pointer' => '/data/0'],
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

        $this->doInvalidRequest("/api/v1/posts/{$post->getKey()}/relationships/author", $data, 'PATCH')
            ->assertStatus((int) $error['status'])
            ->assertExactJson(['errors' => [$error]]);
    }

    /**
     * @param $data
     * @param array $error
     * @dataProvider toManyProvider
     */
    public function testToMany($data, array $error)
    {
        $post = factory(Post::class)->create();

        $this->doInvalidRequest("/api/v1/posts/{$post->getKey()}/relationships/tags", $data, 'PATCH')
            ->assertStatus((int) $error['status'])
            ->assertExactJson(['errors' => [$error]]);
    }
}
