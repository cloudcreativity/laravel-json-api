<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;
use DummyApp\Comment;
use DummyApp\Post;
use DummyApp\User;

/**
 * Class MorphManyTest
 *
 * Test a JSON API has-many relationship that relates to an Eloquent
 * morph-many relationship.
 *
 * In our dummy app, this is the comments relationship on a post model.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MorphManyTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    public function test()
    {
        $this->markTestIncomplete('@todo add other tests for this relationship.');
    }

    /**
     * Test that we can read the related comments.
     */
    public function testReadRelated()
    {
        $model = factory(Post::class)->create();
        $comments = factory(Comment::class, 2)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $model->getKey(),
        ]);

        /** This comment should not appear in the results... */
        factory(Comment::class)->states('post')->create();

        $this->doReadRelated($model, 'comments')
            ->assertReadHasMany('comments', $comments);
    }

    public function testReadRelatedWithFilter()
    {
        $post = factory(Post::class)->create();
        $user = factory(User::class)->create();

        $expected = factory(Comment::class, 2)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
            'user_id' => $user->getKey(),
        ]);

        /** This one should not be found. */
        factory(Comment::class)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $this->doReadRelated($post, 'comments', ['filter' => ['created-by' => $user->getKey()]])
            ->assertReadHasMany('comments', $expected);
    }

    public function testReadRelatedWithInvalidFilter()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelated($post, 'comments', ['filter' => ['created-by' => 'foo']])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('filter.created-by');
    }

    public function testReadRelatedWithSort()
    {
        $a = factory(Comment::class)->states('post')->create([
            'content' => 'Some comment',
        ]);

        /** @var Post $post */
        $post = $a->commentable;

        $b = factory(Comment::class)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $this->doReadRelated($post, 'comments', ['sort' => 'content'])
            ->assertReadHasMany('comments', [$b, $a]);

        $this->markTestIncomplete('@todo this assertion does not assert the order of resources.');
    }

    public function testReadRelatedWithInvalidSort()
    {
        $post = factory(Post::class)->create();

        /** `slug` is a valid sort parameter on the posts resource, but not the comments resource. */
        $this->doReadRelated($post, 'comments', ['sort' => 'slug'])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('sort');
    }

    public function testReadRelatedWithInclude()
    {
        $post = factory(Post::class)->create();
        $comments = factory(Comment::class, 3)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $this->doReadRelated($post, 'comments', ['include' => 'created-by'])
            ->assertReadHasMany('comments', $comments);

        $this->markTestIncomplete('@todo assert that authors are included.');
    }

    public function testReadRelatedWithInvalidInclude()
    {
        $post = factory(Post::class)->create();

        /** `author` is valid on a post but not on a comment. */
        $this->doReadRelated($post, 'comments', ['include' => 'author'])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('include');
    }

    public function testReadRelatedWithPagination()
    {
        $post = factory(Post::class)->create();
        $comments = factory(Comment::class, 3)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $this->doReadRelated($post, 'comments', ['page' => ['number' => 1, 'size' => 2]])
            ->assertReadHasMany('comments', $comments->take(2));

        $this->markTestIncomplete('@todo assert page meta.');
    }

    public function testReadRelatedWithInvalidPagination()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelated($post, 'comments', ['page' => ['number' => 1, 'size' => -1]])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('page.size');
    }

    /**
     * Test that we can read the resource identifiers for the related comments.
     */
    public function testReadRelationship()
    {
        $model = factory(Post::class)->create();
        $comments = factory(Comment::class, 2)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $model->getKey(),
        ]);

        /** This comment should not appear in the results... */
        factory(Comment::class)->states('post')->create();

        $this->doReadRelated($model, 'comments')
            ->assertReadHasManyIdentifiers('comments', $comments);
    }
}
