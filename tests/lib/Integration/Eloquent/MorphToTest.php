<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Comment;
use DummyApp\Post;
use DummyApp\Video;

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
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
    }

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
                'createdBy' => [
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

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->withData($data)
            ->includePaths('createdBy', 'commentable')
            ->post('/api/v1/comments');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/comments'), $data)
            ->id();

        $this->assertDatabaseHas('comments', [
            'id' => $id,
            'commentable_type' => null,
            'commentable_id' => null,
        ]);
    }

    public function testCreateWithRelated()
    {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->states('video')->make();

        $data = [
            'type' => 'comments',
            'attributes' => [
                'content' => $comment->content,
            ],
            'relationships' => [
                'commentable' => [
                    'data' => [
                        'type' => 'videos',
                        'id' => (string) $comment->commentable_id,
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('commentable')
            ->post('/api/v1/comments');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/comments'), $data)
            ->id();

        $this->assertDatabaseHas('comments', [
            'id' => $id,
            'commentable_type' => Video::class,
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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('commentable')
            ->patch(url('/api/v1/comments', $comment));

        $response->assertFetchedOne($data);

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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('commentable')
            ->patch(url('/api/v1/comments', $comment));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->getKey(),
            'commentable_type' => Video::class,
            'commentable_id' => $video->getKey(),
        ]);
    }

    public function testUpdateChangesRelatedResource()
    {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->states('post')->create();

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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('commentable')
            ->patch(url('/api/v1/comments', $comment));

        $response->assertFetchedOne($data);

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
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => $post->title,
            ],
        ];

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/comments', [$comment, 'commentable']));

        $response
            ->assertFetchedOne($expected);
    }

    public function testReadRelatedNull()
    {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->create();

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/comments', [$comment, 'commentable']));

        $response
            ->assertFetchedNull();
    }

    public function testReadRelationship()
    {
        $comment = factory(Comment::class)->states('video')->create();

        $response = $this
            ->jsonApi('videos')
            ->get(url('/api/v1/comments', [$comment, 'relationships', 'commentable']));

        $response
            ->assertFetchedToOne($comment->commentable_id);
    }

    public function testReadEmptyRelationship()
    {
        $comment = factory(Comment::class)->create();

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/comments', [$comment, 'relationships', 'commentable']));

        $response
            ->assertFetchedNull();
    }

    public function testReplaceNullRelationshipWithRelatedResource()
    {
        $comment = factory(Comment::class)->create();
        $post = factory(Post::class)->create();

        $data = ['type' => 'posts', 'id' => (string) $post->getKey()];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/comments', [$comment, 'relationships', 'commentable']));

        $response
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

        $response = $this
            ->jsonApi()
            ->withData(null)
            ->patch(url('/api/v1/comments', [$comment, 'relationships', 'commentable']));

        $response
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
        $post = factory(Post::class)->create();

        $data = ['type' => 'posts', 'id' => (string) $post->getKey()];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/comments', [$comment, 'relationships', 'commentable']));

        $response
            ->assertStatus(204);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->getKey(),
            'commentable_type' => Post::class,
            'commentable_id' => $post->getKey(),
        ]);
    }
}
