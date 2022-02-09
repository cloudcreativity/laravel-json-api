<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('comments')
            ->post('/api/v1/posts');

        $response
            ->assertCreatedWithServerId(url('/api/v1/posts'), $data);

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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('comments')
            ->post('/api/v1/posts');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/posts'), $data)
            ->id();

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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('comments')
            ->post('/api/v1/posts');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/posts'), $data)
            ->id();

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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('comments')
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);

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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('comments')
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);
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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('comments')
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);
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

        $response = $this
            ->jsonApi('comments')
            ->get(url('/api/v1/posts', [$model, 'comments']));

        $response
            ->assertFetchedMany($comments);
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

        $response = $this
            ->jsonApi('comments')
            ->filter(['createdBy' => $user->getRouteKey()])
            ->get(url('/api/v1/posts', [$post, 'comments']));

        $response
            ->assertFetchedMany($expected);
    }

    public function testReadRelatedWithInvalidFilter()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi('comments')
            ->filter(['createdBy' => 'foo'])
            ->get(url('/api/v1/posts', [$post, 'comments']));

        $response->assertErrorStatus([
            'status' => '400',
            'source' => ['parameter' => 'filter.createdBy'],
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

        $response = $this
            ->jsonApi('comments')
            ->sort('content')
            ->get(url('/api/v1/posts', [$post, 'comments']));

        $response
            ->assertFetchedManyInOrder([$b, $a]);
    }

    public function testReadRelatedWithInvalidSort()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi('comments')
            ->sort('slug')
            ->get(url('/api/v1/posts', [$post, 'comments']));

        /** `slug` is a valid sort parameter on the posts resource, but not the comments resource. */
        $response->assertError(400, [
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
            return ['type' => 'users', 'id' => $comment->user];
        })->all();

        $response = $this
            ->jsonApi('comments')
            ->includePaths('createdBy')
            ->get(url('/api/v1/posts', [$post, 'comments']));

        $response
            ->assertFetchedMany($comments)
            ->assertIncluded($expected);
    }

    public function testReadRelatedWithInvalidInclude()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi('comments')
            ->includePaths('author')
            ->get(url('/api/v1/posts', [$post, 'comments']));

        /** `author` is valid on a post but not on a comment. */
        $response->assertError(400, [
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

        $response = $this
            ->jsonApi('comments')
            ->page(['limit' => 2])
            ->get(url('/api/v1/posts', [$post, 'comments']));

        $response
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

        $response = $this
            ->jsonApi('comments')
            ->page(['limit' => 100])
            ->get(url('/api/v1/posts', [$post, 'comments']));

        $response->assertError(400, [
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

        $response = $this
            ->jsonApi('comments')
            ->get(url('/api/v1/posts', [$model, 'relationships', 'comments']));

        $response
            ->assertFetchedToMany($comments);
    }

    public function testReadEmptyRelationship()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi('comments')
            ->get(url('/api/v1/posts', [$post, 'relationships', 'comments']));

        $response
            ->assertFetchedNone();
    }

    public function testReplaceEmptyRelationshipWithRelatedResource()
    {
        $post = factory(Post::class)->create();
        $comments = factory(Comment::class, 2)->create();

        $data = $comments->map(function (Comment $comment) {
            return ['type' => 'comments', 'id' => (string) $comment->getRouteKey()];
        })->all();

        $response = $this
            ->jsonApi('comments')
            ->withData($data)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'comments']));

        $response
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

        $response = $this
            ->jsonApi('comments')
            ->withData([])
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'comments']));

        $response
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

        $response = $this
            ->jsonApi('comments')
            ->withData($data)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'comments']));

        $response
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

        $response = $this
            ->jsonApi('comments')
            ->withData($data)
            ->post(url('/api/v1/posts', [$post, 'relationships', 'comments']));

        $response
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

        $response = $this
            ->jsonApi('comments')
            ->withData($data)
            ->delete(url('/api/v1/posts', [$post, 'relationships', 'comments']));

        $response
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
