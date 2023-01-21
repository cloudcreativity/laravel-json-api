<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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
     * If we submit a create request with a sort parameter that is allowed for querying,
     * it is rejected because create does not support sorting.
     */
    public function testCreateRejectsSort()
    {
        $comment = factory(Comment::class)->states('post')->make();
        $data = $this->serialize($comment);

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->withData($data)
            ->sort('created-at')
            ->post('/api/v1/comments');

        $response->assertError(400, [
            'source' => ['parameter' => 'sort'],
        ]);
    }

    /**
     * If we submit a create request with a filter parameter that is allowed for querying,
     * it is rejected because create does not support filtering.
     */
    public function testCreateRejectsFilter()
    {
        $comment = factory(Comment::class)->states('post')->make();
        $data = $this->serialize($comment);

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->withData($data)
            ->filter(['created-by' => '1'])
            ->post('/api/v1/comments');

        $response->assertError(400, [
            'source' => ['parameter' => 'filter'],
        ]);
    }

    /**
     * If we submit a create request with a page parameter that is allowed for querying,
     * it is rejected because create does not support pagination.
     */
    public function testCreateRejectsPage()
    {
        $comment = factory(Comment::class)->states('post')->make();
        $data = $this->serialize($comment);

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->withData($data)
            ->page(['size' => '12'])
            ->post('/api/v1/comments');

        $response->assertError(400, [
            'source' => ['parameter' => 'page'],
        ]);
    }

    /**
     * If we submit a read request with a sort parameter that is allowed for querying,
     * it is rejected because read does not support sorting.
     */
    public function testReadRejectsSort()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->sort('created-at')
            ->get(url('/api/v1/comments', $comment));

        $response->assertError(400, [
            'source' => ['parameter' => 'sort'],
        ]);
    }

    /**
     * If we submit a read request with a page parameter that is allowed for querying,
     * it is rejected because read does not support pagination.
     */
    public function testReadRejectsPage()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->page(['size' => '12'])
            ->get(url('/api/v1/comments', $comment));

        $response->assertError(400, [
            'source' => ['parameter' => 'page'],
        ]);
    }

    /**
     * If we submit an update request with a sort parameter that is allowed for querying,
     * it is rejected because update does not support sorting.
     */
    public function testUpdateRejectsSort()
    {
        $comment = factory(Comment::class)->states('post')->create();
        $data = $this->serialize($comment);

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->withData($data)
            ->sort('created-at')
            ->patch(url('/api/v1/comments', $comment));

        $response->assertError(400, [
            'source' => ['parameter' => 'sort'],
        ]);
    }

    /**
     * If we submit an update request with a filter parameter that is allowed for querying,
     * it is rejected because update does not support filtering.
     */
    public function testUpdateRejectsFilter()
    {
        $comment = factory(Comment::class)->states('post')->create();
        $data = $this->serialize($comment);

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->withData($data)
            ->filter(['created-by' => '1'])
            ->patch(url('/api/v1/comments', $comment));

        $response->assertError(400, [
            'source' => ['parameter' => 'filter'],
        ]);
    }

    /**
     * If we submit an update request with a page parameter that is allowed for querying,
     * it is rejected because update does not support pagination.
     */
    public function testUpdateRejectsPage()
    {
        $comment = factory(Comment::class)->states('post')->create();
        $data = $this->serialize($comment);

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->withData($data)
            ->page(['size' => '12'])
            ->patch(url('/api/v1/comments', $comment));

        $response->assertError(400, [
            'source' => ['parameter' => 'page'],
        ]);
    }

    /**
     * If we submit a delete request with a sort parameter that is allowed for querying,
     * it is rejected because delete does not support sorting.
     */
    public function testDeleteRejectsSort()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->sort('created-at')
            ->delete(url('/api/v1/comments', $comment));

        $response->assertError(400, [
            'source' => ['parameter' => 'sort'],
        ]);
    }

    /**
     * If we submit a delete request with a filter parameter that is allowed for querying,
     * it is rejected because delete does not support filtering.
     */
    public function testDeleteRejectsFilter()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->filter(['created-by' => '1'])
            ->delete(url('/api/v1/comments', $comment));

        $response->assertError(400, [
            'source' => ['parameter' => 'filter'],
        ]);
    }

    /**
     * If we submit a delete request with a page parameter that is allowed for querying,
     * it is rejected because delete does not support pagination.
     */
    public function testDeleteRejectsPage()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->page(['size' => '12'])
            ->delete(url('/api/v1/comments', $comment));

        $response->assertError(400, [
            'source' => ['parameter' => 'page'],
        ]);
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
