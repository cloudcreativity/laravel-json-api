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

use DummyApp\Comment;

class QueryParameterValidationTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'comments';

    /**
     * If we submit a create request with a sort parameter that is allowed for querying,
     * it is rejected because create does not support sorting.
     */
    public function testCreateRejectsSort()
    {
        $comment = factory(Comment::class)->states('post')->make();
        $data = $this->serialize($comment);

        $this->actingAs($comment->user)
            ->doCreate($data, ['sort' => 'created-at'])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('sort');
    }

    /**
     * If we submit a create request with a filter parameter that is allowed for querying,
     * it is rejected because create does not support filtering.
     */
    public function testCreateRejectsFilter()
    {
        $comment = factory(Comment::class)->states('post')->make();
        $data = $this->serialize($comment);

        $this->actingAs($comment->user)
            ->doCreate($data, ['filter' => ['created-by' => '1']])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('filter');
    }

    /**
     * If we submit a create request with a page parameter that is allowed for querying,
     * it is rejected because create does not support pagination.
     */
    public function testCreateRejectsPage()
    {
        $comment = factory(Comment::class)->states('post')->make();
        $data = $this->serialize($comment);

        $this->actingAs($comment->user)
            ->doCreate($data, ['page' => ['size' => 12]])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('page');
    }

    /**
     * If we submit a read request with a sort parameter that is allowed for querying,
     * it is rejected because read does not support sorting.
     */
    public function testReadRejectsSort()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $this->actingAs($comment->user)
            ->doRead($comment, ['sort' => 'created-at'])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('sort');
    }

    /**
     * If we submit a read request with a page parameter that is allowed for querying,
     * it is rejected because read does not support pagination.
     */
    public function testReadRejectsPage()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $this->actingAs($comment->user)
            ->doRead($comment, ['page' => ['size' => 12]])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('page');
    }

    /**
     * If we submit an update request with a sort parameter that is allowed for querying,
     * it is rejected because update does not support sorting.
     */
    public function testUpdateRejectsSort()
    {
        $comment = factory(Comment::class)->states('post')->create();
        $data = $this->serialize($comment);

        $this->actingAs($comment->user)
            ->doUpdate($data, ['sort' => 'created-at'])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('sort');
    }

    /**
     * If we submit an update request with a filter parameter that is allowed for querying,
     * it is rejected because update does not support filtering.
     */
    public function testUpdateRejectsFilter()
    {
        $comment = factory(Comment::class)->states('post')->create();
        $data = $this->serialize($comment);

        $this->actingAs($comment->user)
            ->doUpdate($data, ['filter' => ['created-by' => '1']])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('filter');
    }

    /**
     * If we submit an update request with a page parameter that is allowed for querying,
     * it is rejected because update does not support pagination.
     */
    public function testUpdateRejectsPage()
    {
        $comment = factory(Comment::class)->states('post')->create();
        $data = $this->serialize($comment);

        $this->actingAs($comment->user)
            ->doUpdate($data, ['page' => ['size' => 12]])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('page');
    }

    /**
     * If we submit a delete request with a sort parameter that is allowed for querying,
     * it is rejected because delete does not support sorting.
     */
    public function testDeleteRejectsSort()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $this->actingAs($comment->user)
            ->doDelete($comment, ['sort' => 'created-at'])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('sort');
    }

    /**
     * If we submit a delete request with a filter parameter that is allowed for querying,
     * it is rejected because delete does not support filtering.
     */
    public function testDeleteRejectsFilter()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $this->actingAs($comment->user)
            ->doDelete($comment, ['filter' => ['created-by' => '1']])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('filter');
    }

    /**
     * If we submit a delete request with a page parameter that is allowed for querying,
     * it is rejected because delete does not support pagination.
     */
    public function testDeleteRejectsPage()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $this->actingAs($comment->user)
            ->doDelete($comment, ['page' => ['size' => 12]])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('page');
    }

    /**
     * @param Comment $comment
     * @return array
     */
    private function serialize(Comment $comment)
    {
        $data = [
            'type' => 'comments',
            'attributes' => [
                'content' => $comment->content,
            ],
            'relationships' => [
                'commentable' => [
                    'data' => [
                        'type' => 'posts',
                        'id' => (string) $comment->commentable_id,
                    ],
                ],
            ],
        ];

        if ($comment->getKey()) {
            $data['id'] = $comment->getKey();
        }

        return $data;
    }
}
