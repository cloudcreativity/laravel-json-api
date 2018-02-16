<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;
use DummyApp\Comment;
use DummyApp\Post;

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
    public function testReadComments()
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

    /**
     * Test that we can read the resource identifiers for the related comments.
     */
    public function testReadCommentsRelationship()
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
