<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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
            'data.id:client id not allowed' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'id' => 'foobar',
                        'attributes' => ['title' => 'Hello World'],
                    ],
                ],
                [
                    'title' => 'Not Supported',
                    'detail' => 'Resource type posts does not support client-generated IDs.',
                    'status' => '403',
                    'source' => ['pointer' => '/data/id'],
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
            'data.attributes:type not allowed' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'type' => 'foo',
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member attributes cannot have a type field.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/attributes'],
                ],
            ],
            'data.attributes:id not allowed' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'id' => '123',
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member attributes cannot have a id field.",
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
            'data.relationships:type not allowed' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'relationships' => [
                            'type' => [
                                'data' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member relationships cannot have a type field.",
                    'status' => '400',
                    'source' => ['pointer' => '/data/relationships'],
                ],
            ],
            'data.relationships:id not allowed' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'relationships' => [
                            'id' => [
                                'data' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => "The member relationships cannot have a id field.",
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
                    'source' => ['pointer' => '/data/relationships/tags/data/0'],
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
                            'author' => [
                                'data' => null,
                            ],
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
            'fields:duplicate' => [
                [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'author' => null,
                        ],
                        'relationships' => [
                            'author' => [
                                'data' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Non-Compliant JSON API Document',
                    'detail' => 'The author field cannot exist as an attribute and a relationship.',
                    'status' => '400',
                    'source' => ['pointer' => '/data'],
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
            ->assertErrorStatus($error);
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
            ->assertErrorStatus($error);
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

        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/comments');

        $response->assertStatus(400)->assertJson([
            'errors' => [
                [
                    'detail' => "Resource type post is not recognised.",
                    'status' => '400',
                    'source' => [
                        'pointer' => '/data/relationships/commentable/data/type',
                    ],
                ]
            ],
        ]);
    }

    public function testIssue305(): void
    {
        $data = [
            'type' => 'single',
            'data' => [
                [
                    'key-1' => 'value-1'
                ],
            ],
        ];

        $expected = [
            'status' => '400',
            'title' => 'Non-Compliant JSON API Document',
            'detail' => 'The member data must be an object.',
            'source' => [
                'pointer' => '/data',
            ],
        ];

        $this->doInvalidRequest('/api/v1/posts', $data)
            ->assertErrorStatus($expected);
    }
}
