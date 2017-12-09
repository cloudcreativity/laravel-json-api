<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;

class CommentsTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'comments';

    public function testCreate()
    {
        $model = factory(Comment::class)->states('post')->make();

        $data = [
            'type' => 'comments',
            'attributes' => [
                'content' => $model->content,
            ],
            'relationships' => [
                'commentable' => [
                    'data' => [
                        'type' => 'posts',
                        'id' => (string) $model->commentable_id,
                    ],
                ],
            ],
        ];

        $this->actingAs($model->user);

        $expected = $data;
        $expected['relationships']['created-by'] = [
            'data' => ['type' => 'users', 'id' => $model->user_id],
        ];

        $id = $this
            ->expectSuccess()
            ->doCreate($data)
            ->assertCreateResponse($expected);

        $this->assertModelCreated($model, $id);
    }

    public function testRead()
    {
        $model = factory(Comment::class)->states('post')->create();

        $data = [
            'type' => 'comments',
            'id' => (string) $model->getKey(),
            'attributes' => [
                'content' => $model->content,
            ],
            'relationships' => [
                'commentable' => [
                    'data' => [
                        'type' => 'posts',
                        'id' => $model->commentable_id,
                    ],
                ],
                'created-by' => [
                    'data' => [
                        'type' => 'users',
                        'id' => $model->user_id,
                    ],
                ],
            ],
        ];

        $this->expectSuccess()
            ->doRead($model)
            ->assertReadResponse($data);
    }

}
