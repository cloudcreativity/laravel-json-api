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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Validation\Spec;

use DummyApp\Comment;
use DummyApp\Post;

class ResourceValidationTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @return array
     */
    public function postProvider()
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
                ['data' => []],
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
                        'attributes' => ['title' => 'Hello World'],
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
                        'attributes' => ['title' => 'Hello World'],
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
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/type'],
                ],
            ],
            'data.type:not supported' => [
                [
                    'data' => [
                        'type' => 'users',
                        'attributes' => ['name' => 'John Doe'],
                    ],
                ],
                [
                    'title' => 'Not Supported',
                    'detail' => "Resource type users is not supported by this endpoint.",
                    'status' => '409',
                    'source' => ['pointer' => '/data/type'],
                ],
            ],
            'data.attributes:not object' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member attributes must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/attributes'],
                ],
            ],
            'data.relationships:not object' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member relationships must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships'],
                ],
            ],
            'data.relationships.*:not object' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member author must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/author'],
                ],
            ],
            'data.relationships.*.data:required' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [
                                'meta' => ['foo' => 'bar'],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/author'],
                ],
            ],
            'data.relationships.*.data:not object' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [
                                'data' => false,
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member data must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/author/data'],
                ],
            ],
            'data.relationships.*.data:resource does not exist' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'author' => [
                                'data' => [
                                    'type' => 'users',
                                    'id' => '10',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Not Found',
                    'detail' => 'The related resource does not exist.',
                    'status' => '404',
                    'source' => ['pointer' => '/data/relationships/author'],
                ],
            ],
            'data.relationships.*.data.*:not object' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'tags' => [
                                'data' => [
                                    [],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member 0 must be an object.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/tags/data'],
                ],
            ],
            'data.relationships.*.data.*.type:required' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'title' => 'Hello World',
                            'content' => '...',
                            'slug' => 'hello-world',
                        ],
                        'relationships' => [
                            'tags' => [
                                'data' => [
                                    [
                                        'id' => '1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member type is required.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships/tags/data/0'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function patchProvider()
    {
        return [
            'data.id:required' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => ['title' => 'Hello World'],
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
                        'type' => 'posts',
                        'id' => null,
                        'attributes' => ['title' => 'Hello World'],
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
                        'type' => 'posts',
                        'id' => 1,
                        'attributes' => ['title' => 'Hello World'],
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
                        'type' => 'posts',
                        'id' => '',
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member id cannot be empty.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/id'],
                ],
            ],
            'data.id:not supported' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'id' => '10',
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Not Supported',
                    'detail' => "Resource id 10 is not supported by this endpoint.",
                    'status' => '409',
                    'source' => ['pointer' => '/data/id'],
                ],
            ],
        ];
    }

    /**
     * @param $data
     * @param array $error
     * @dataProvider postProvider
     */
    public function testPost($data, array $error)
    {
        $this->doInvalidRequest('/api/v1/posts', $data)
            ->assertStatus((int) $error['status'])
            ->assertExactJson(['errors' => [$error]]);
    }

    /**
     * @param $data
     * @param array $error
     * @dataProvider patchProvider
     */
    public function testPatch($data, array $error)
    {
        $post = factory(Post::class)->create();

        if (1 != $post->getKey()) {
            $this->fail('Test scenario expects id to be 1.');
        }

        $this->doInvalidRequest("/api/v1/posts/{$post->getKey()}", $data, 'PATCH')
            ->assertStatus((int) $error['status'])
            ->assertExactJson(['errors' => [$error]]);
    }

    /**
     * The client must receive a 400 error with a correct JSON API pointer if an invalid
     * resource type is sent for a resource relationship.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/139
     */
    public function testRejectsUnrecognisedTypeInResourceRelationship()
    {
        $this->resourceType = 'comments';
        $comment = factory(Comment::class)->states('post')->make();

        $data = [
            'type' => 'comments',
            'attributes' => [
                'content' => $comment->content,
            ],
            'relationships' => [
                'commentable' => [
                    'data' => [
                        'type' => 'post', // invalid type as expecting the plural,
                        'id' => (string) $comment->commentable_id,
                    ],
                ],
            ],
        ];

        $this->actingAsUser()->doCreate($data)->assertStatus(400)->assertJson([
            'errors' => [
                [
                    'detail' => "Resource type 'post' is not recognised.",
                    'status' => '400',
                    'source' => [
                        'pointer' => '/data/relationships/commentable/data/type',
                    ],
                ]
            ],
        ]);
    }
}
