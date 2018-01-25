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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;

class ValidationTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->withDefaultApi([], function (ApiGroup $api) {
            $api->resource('posts');
            $api->resource('comments');
        });
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
        $comment = factory(Comment::class)->make();

        $data = [
            'type' => 'comments',
            'attributes' => [
                'content' => $comment->content,
            ],
            'relationships' => [
                'post' => [
                    'data' => [
                        'type' => 'post', // invalid type as expecting the plural,
                        'id' => (string) $comment->post_id,
                    ],
                ],
            ],
        ];

        $this->doCreate($data)
            ->assertStatus(400)
            ->assertErrors()
            ->assertPointers('/data/relationships/post/data/type');
    }
}
