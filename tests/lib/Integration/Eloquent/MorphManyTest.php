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
            'content' => 'A comment',
        ]);

        $this->doReadRelated($post, 'comments', ['sort' => 'content'])
            ->assertReadHasMany('comments', [$b, $a]);
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

        $response = $this
            ->doReadRelated($post, 'comments', ['include' => 'created-by'])
            ->assertReadHasMany('comments', $comments);

        $expected = $comments->map(function (Comment $comment) {
            return $comment->user;
        });

        $response->assertDocument()
            ->assertIncluded()
            ->assertContainsOnly(['users' => $this->normalizeIds($expected)]);
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

        $response = $this
            ->doReadRelated($post, 'comments', ['page' => ['number' => 1, 'size' => 2]])
            ->assertReadHasMany('comments', $comments->take(2));

        $response->assertDocument()
            ->assertMetaSubset(['page' => ['current-page' => 1, 'per-page' => 2]]);
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
