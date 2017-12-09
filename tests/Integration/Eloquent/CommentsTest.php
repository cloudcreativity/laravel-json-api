<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;

class CommentsTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'comments';

    public function testCreate()
    {
        $comment = factory(Comment::class)->states('post')->make();

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

        $this->actingAs($comment->user);

        $expected = $data;
        $expected['relationships']['created-by'] = [
            'data' => ['type' => 'users', 'id' => $comment->user_id],
        ];

        $id = $this
            ->expectSuccess()
            ->doCreate($data)
            ->assertCreatedWithId($expected);

        $this->assertModelCreated($comment, $id);
    }

    public function testRead()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $data = [
            'type' => 'comments',
            'id' => (string) $comment->getKey(),
            'attributes' => [
                'content' => $comment->content,
            ],
            'relationships' => [
                'commentable' => [
                    'data' => [
                        'type' => 'posts',
                        'id' => $comment->commentable_id,
                    ],
                ],
                'created-by' => [
                    'data' => [
                        'type' => 'users',
                        'id' => $comment->user_id,
                    ],
                ],
            ],
        ];

        $this->expectSuccess()
            ->doRead($comment)
            ->assertRead($data);
    }

    public function testReadCommentable()
    {
        $comment = factory(Comment::class)->states('post')->create();
        /** @var Post $post */
        $post = $comment->commentable;

        $data = [
            'type' => 'posts',
            'id' => $post->getKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
        ];

        $this->expectSuccess()->doReadRelated($comment, 'commentable')->assertReadHasOne($data);
    }

}
