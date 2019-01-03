<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
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

    public function testCreateWithEmpty()
    {
        $post = factory(Post::class)->make();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'comments' => [
                    'data' => [],
                ],
            ],
        ];

        $this->doCreate($data, ['include' => 'comments'])->assertCreatedWithId($data);

        $this->assertDatabaseMissing('comments', [
            'commentable_type' => Post::class,
        ]);
    }

    public function testCreateWithRelated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->make();
        /** @var Comment $comment */
        $comment = factory(Comment::class)->create();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'comments' => [
                    'data' => [
                        [
                            'type' => 'comments',
                            'id' => (string) $comment->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $id = $this
            ->doCreate($data, ['include' => 'comments'])
            ->assertCreatedWithId($data);

        $this->assertCommentIs(Post::find($id), $comment);
    }

    public function testCreateWithManyRelated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->make();
        $comments = factory(Comment::class, 2)->create();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'comments' => [
                    'data' => [
                        [
                            'type' => 'comments',
                            'id' => (string) $comments->first()->getRouteKey(),
                        ],
                        [
                            'type' => 'comments',
                            'id' => (string) $comments->last()->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $id = $this
            ->doCreate($data, ['include' => 'comments'])
            ->assertCreatedWithId($data);

        $this->assertCommentsAre(Post::find($id), $comments);
    }

    public function testUpdateReplacesRelationshipWithEmptyRelationship()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        factory(Comment::class, 2)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'relationships' => [
                'comments' => [
                    'data' => [],
                ],
            ],
        ];

        $this->doUpdate($data, ['include' => 'comments'])->assertUpdated($data);

        $this->assertDatabaseMissing('comments', [
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);
    }

    public function testUpdateReplacesEmptyRelationshipWithResource()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        $comment = factory(Comment::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'comments' => [
                    'data' => [
                        [
                            'type' => 'comments',
                            'id' => (string) $comment->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $this->doUpdate($data, ['include' => 'comments'])->assertUpdated($data);
        $this->assertCommentIs($post, $comment);
    }

    public function testUpdateChangesRelatedResources()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        factory(Comment::class, 3)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $comments = factory(Comment::class, 2)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'comments' => [
                    'data' => [
                        [
                            'type' => 'comments',
                            'id' => (string) $comments->first()->getRouteKey(),
                        ],
                        [
                            'type' => 'comments',
                            'id' => (string) $comments->last()->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $this->doUpdate($data, ['include' => 'comments'])->assertUpdated($data);
        $this->assertCommentsAre($post, $comments);
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

        $this->doReadRelated($post, 'comments', ['filter' => ['created-by' => $user->getRouteKey()]])
            ->assertReadHasMany('comments', $expected);
    }

    public function testReadRelatedWithInvalidFilter()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelated($post, 'comments', ['filter' => ['created-by' => 'foo']])->assertError(400, [
            'source' => ['parameter' => 'filter.created-by'],
        ]);
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
        $this->doReadRelated($post, 'comments', ['sort' => 'slug'])->assertError(400, [
            'source' => ['parameter' => 'sort'],
        ]);
    }

    public function testReadRelatedWithInclude()
    {
        $post = factory(Post::class)->create();
        $comments = factory(Comment::class, 3)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);


        $expected = $comments->map(function (Comment $comment) {
            return ['type' => 'users', 'id' => (string) $comment->user_id];
        })->all();

        $this->doReadRelated($post, 'comments', ['include' => 'created-by'])
            ->assertReadHasMany('comments', $comments)
            ->assertIncluded($expected);
    }

    public function testReadRelatedWithInvalidInclude()
    {
        $post = factory(Post::class)->create();

        /** `author` is valid on a post but not on a comment. */
        $this->doReadRelated($post, 'comments', ['include' => 'author'])->assertError(400, [
            'source' => ['parameter' => 'include'],
        ]);
    }

    public function testReadRelatedWithPagination()
    {
        $post = factory(Post::class)->create();
        $comments = factory(Comment::class, 3)->create([
            'created_at' => Carbon::now(),
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ])->sortByDesc('id')->values();

        $this->doReadRelated($post, 'comments', ['page' => ['limit' => 2]])
            ->willSeeType('comments')
            ->assertFetchedPage($comments->take(2), null, [
                'per-page' => 2,
                'from' => (string) $comments->first()->getRouteKey(),
                'to' => (string) $comments->get(1)->getRouteKey(),
                'has-more' => true,
            ]);
    }

    public function testReadRelatedWithInvalidPagination()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelated($post, 'comments', ['page' => ['limit' => 100]])->assertError(400, [
            'source' => ['parameter' => 'page.limit'],
        ]);
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

    public function testReadEmptyRelationship()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelationship($post, 'comments')
            ->assertReadHasManyIdentifiers(null);
    }

    public function testReplaceEmptyRelationshipWithRelatedResource()
    {
        $post = factory(Post::class)->create();
        $comments = factory(Comment::class, 2)->create();

        $data = $comments->map(function (Comment $comment) {
            return ['type' => 'comments', 'id' => (string) $comment->getRouteKey()];
        })->all();

        $this->doReplaceRelationship($post, 'comments', $data)
            ->assertStatus(204);

        $this->assertCommentsAre($post, $comments);
    }

    public function testReplaceRelationshipWithNone()
    {
        $post = factory(Post::class)->create();
        factory(Comment::class, 2)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $this->doReplaceRelationship($post, 'comments', [])
            ->assertStatus(204);

        $this->assertFalse($post->comments()->exists());
    }

    public function testReplaceRelationshipWithDifferentResources()
    {
        $post = factory(Post::class)->create();
        factory(Comment::class, 2)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $comments = factory(Comment::class, 3)->create();

        $data = $comments->map(function (Comment $comment) {
            return ['type' => 'comments', 'id' => (string) $comment->getRouteKey()];
        })->all();

        $this->doReplaceRelationship($post, 'comments', $data)
            ->assertStatus(204);

        $this->assertCommentsAre($post, $comments);
    }

    public function testAddToRelationship()
    {
        $post = factory(Post::class)->create();
        $existing = factory(Comment::class, 2)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $add = factory(Comment::class, 2)->create();
        $data = $add->map(function (Comment $comment) {
            return ['type' => 'comments', 'id' => (string) $comment->getRouteKey()];
        })->all();

        $this->doAddToRelationship($post, 'comments', $data)
            ->assertStatus(204);

        $this->assertCommentsAre($post, $existing->merge($add));
    }

    public function testRemoveFromRelationship()
    {
        $post = factory(Post::class)->create();
        $comments = factory(Comment::class, 4)->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);

        $data = $comments->take(2)->map(function (Comment $comment) {
            return ['type' => 'comments', 'id' => (string) $comment->getRouteKey()];
        })->all();

        $this->doRemoveFromRelationship($post, 'comments', $data)
            ->assertStatus(204);

        $this->assertCommentsAre($post, [$comments->get(2), $comments->get(3)]);
    }

    /**
     * @param $post
     * @param $comment
     * @return void
     */
    private function assertCommentIs(Post $post, Comment $comment)
    {
        $this->assertCommentsAre($post, [$comment]);
    }

    /**
     * @param Post $post
     * @param iterable $comments
     * @return void
     */
    private function assertCommentsAre(Post $post, $comments)
    {
        $this->assertSame(count($comments), $post->comments()->count());

        /** @var Comment $comment */
        foreach ($comments as $comment) {
            $this->assertDatabaseHas('comments', [
                $comment->getKeyName() => $comment->getKey(),
                'commentable_type' => Post::class,
                'commentable_id' => $post->getKey(),
            ]);
        }
    }
}
