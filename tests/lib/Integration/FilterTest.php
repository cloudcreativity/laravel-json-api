<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use DummyApp\Comment;
use DummyApp\Post;
use DummyApp\User;

class FilterTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'comments';


    /**
     * The `id` filter must work with other filters. In this example, if
     * we filter for `id` plus `created-by` we are asking: *of these
     * comments, which were created by the specified user?*
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/219
     */
    public function testIdAsMultiple()
    {
        $user = factory(User::class)->create();
        $comments = factory(Comment::class, 2)->create([
            'user_id' => $user->getKey(),
        ]);

        $other = factory(Comment::class)->create();

        $filter = ['filter' => ['created-by' => $user->getRouteKey()]];

        $this->actingAsUser()
            ->doSearchById([$comments[0], $comments[1], $other], $filter)
            ->assertSearchedIds($comments);
    }

    public function testIdWithPaging()
    {
        $comments = factory(Comment::class, 3)->create();

        $this->actingAsUser()
            ->doSearchById($comments, ['page' => ['number' => 1, 'size' => 2]])
            ->assertSearchedIds([$comments[0], $comments[1]])
            ->assertJson([
                'meta' => [
                    'page' => [
                        'current-page' => 1,
                        'per-page' => 2,
                    ],
                ],
            ]);
    }

    public function testToManyId()
    {
        $post = factory(Post::class)->create();
        $comments = factory(Comment::class, 3)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $ids = $this->normalizeIds([$comments[0], $comments[2], '999']);

        $this->resourceType = 'posts';
        $this->actingAsUser()
            ->doReadRelated($post, 'comments', ['filter' => ['id' => $ids]])
            ->assertReadHasMany('comments', [$comments[0], $comments[2]]);
    }
}
