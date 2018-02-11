<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use CloudCreativity\LaravelJsonApi\Tests\Models\User;
use CloudCreativity\LaravelJsonApi\Tests\Models\Video;

/**
 * Class MorphToTest
 *
 * Tests a JSON API has-one relationship that relates to an Eloquent morph-to
 * relationship.
 *
 * In our dummy app, this is the commentable relationship on the comment model.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MorphToTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'comments';

    public function testCreateWithNull()
    {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->make();

        $data = [
            'type' => 'comments',
            'attributes' => [
                'content' => $comment->content,
            ],
            'relationships' => [
                'created-by' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $comment->user_id,
                    ],
                ],
                'commentable' => [
                    'data' => null,
                ],
            ],
        ];

        $id = $this
            ->expectSuccess()
            ->doCreate($data)
            ->assertCreatedWithId($data);

        $this->assertDatabaseHas('comments', [
            'id' => $id,
            'commentable_type' => null,
            'commentable_id' => null,
        ]);
    }

    public function testCreateWithRelated()
    {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->states('post')->make();

        $data = [
            'type' => 'comments',
            'attributes' => [
                'content' => $comment->content,
            ],
            'relationships' => [
                'created-by' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $comment->user_id,
                    ],
                ],
                'commentable' => [
                    'data' => [
                        'type' => 'posts',
                        'id' => (string) $comment->commentable_id,
                    ],
                ],
            ],
        ];

        $id = $this
            ->doCreate($data)
            ->assertCreatedWithId($data);

        $this->assertDatabaseHas('comments', [
            'id' => $id,
            'commentable_type' => Post::class,
            'commentable_id' => $comment->commentable_id,
        ]);
    }

    public function testUpdateReplacesRelationshipWithNull()
    {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->states('video')->create();

        $data = [
            'type' => 'comments',
            'id' => (string) $comment->getKey(),
            'relationships' => [
                'commentable' => [
                    'data' => null,
                ],
            ],
        ];

        $this->doUpdate($data)->assertUpdated($data);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->getKey(),
            'commentable_type' => null,
            'commentable_id' => null,
        ]);
    }

    public function testUpdateReplacesNullRelationshipWithResource()
    {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->states('video')->create();

        /** @var Video $video */
        $video = factory(Video::class)->create();

        $data = [
            'type' => 'comments',
            'id' => (string) $comment->getKey(),
            'relationships' => [
                'commentable' => [
                    'data' => [
                        'type' => 'videos',
                        'id' => (string) $video->getKey(),
                    ],
                ],
            ],
        ];

        $this->doUpdate($data)->assertUpdated($data);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->getKey(),
            'commentable_type' => Video::class,
            'commentable_id' => $video->getKey(),
        ]);
    }

    public function testUpdateChangesRelatedResource()
    {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->states('video')->create();

        /** @var Post $post */
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'comments',
            'id' => (string) $comment->getKey(),
            'relationships' => [
                'commentable' => [
                    'data' => [
                        'type' => 'posts',
                        'id' => (string) $post->getKey(),
                    ],
                ],
            ],
        ];

        $this->doUpdate($data)->assertUpdated($data);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->getKey(),
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);
    }

    public function testReadRelated()
    {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->states('post')->create();
        /** @var Post $post */
        $post = $comment->commentable;

        $expected = [
            'type' => 'posts',
            'id' => $post->getKey(),
            'attributes' => [
                'title' => $post->title,
            ],
        ];

        $this->doReadRelated($comment, 'commentable')
            ->assertReadHasOne($expected);
    }

    public function testReadRelatedNull()
    {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->create();

        $this->doReadRelated($comment, 'commentable')
            ->assertReadHasOne(null);
    }

    public function testReadRelationship()
    {
        $comment = factory(Comment::class)->states('video')->create();

        $this->doReadRelationship($comment, 'commentable')
            ->assertReadHasOneIdentifier('videos', $comment->commentable_id);
    }

    public function testReadEmptyRelationship()
    {
        $comment = factory(Comment::class)->create();

        $this->doReadRelationship($comment, 'commentable')
            ->assertReadHasOneIdentifier(null);
    }

    public function testReplaceNullRelationshipWithRelatedResource()
    {
        $comment = factory(Comment::class)->create();
        $post = factory(Post::class)->create();

        $data = ['type' => 'posts', 'id' => (string) $post->getKey()];

        $this->doReplaceRelationship($comment, 'commentable', $data)
            ->assertStatus(204);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->getKey(),
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);
    }

    public function testReplaceRelationshipWithNull()
    {
        $comment = factory(Comment::class)->states('post')->create();

        $this->doReplaceRelationship($comment, 'commentable', null)
            ->assertStatus(204);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->getKey(),
            'commentable_type' => null,
            'commentable_id' => null,
        ]);
    }

    public function testReplaceRelationshipWithDifferentResource()
    {
        $comment = factory(Comment::class)->states('post')->create();
        $video = factory(Video::class)->create();

        $data = ['type' => 'videos', 'id' => (string) $video->getKey()];

        $this->doReplaceRelationship($comment, 'commentable', $data)
            ->assertStatus(204);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->getKey(),
            'commentable_type' => Video::class,
            'commentable_id' => $video->getKey(),
        ]);
    }
}
