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
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Comment;
use DummyApp\JsonApi\Posts\Schema;
use DummyApp\Post;
use DummyApp\Tag;
use Illuminate\Support\Facades\Event;

class ResourceTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2018-12-01 12:00:00');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    /**
     * Test searching with a sort parameter.
     */
    public function testSortedSearch()
    {
        $a = factory(Post::class)->create([
            'title' => 'Title A',
        ]);

        $b = factory(Post::class)->create([
            'title' => 'Title B',
        ]);

        $response = $this
            ->jsonApi('posts')
            ->sort('-title')
            ->get('/api/v1/posts');

        $response
            ->assertFetchedManyInOrder([$b, $a]);
    }

    public function testEmptySort(): void
    {
        $posts = factory(Post::class, 2)->create();

        $response = $this
            ->jsonApi('posts')
            ->get('/api/v1/posts?sort=');

        $response
            ->assertFetchedMany($posts);
    }

    public function testFilteredSearch()
    {
        $a = factory(Post::class)->create([
            'title' => 'My First Post',
        ]);

        $b = factory(Post::class)->create([
            'title' => 'My Second Post',
        ]);

        factory(Post::class)->create([
            'title' => 'Some Other Post',
        ]);

        $response = $this
            ->jsonApi('posts')
            ->filter(['title' => 'My'])
            ->get('/api/v1/posts');

        $response
            ->assertFetchedMany([$a, $b]);
    }

    public function testInvalidFilter()
    {
        $response = $this
            ->jsonApi('posts')
            ->filter(['title' => ''])
            ->get('/api/v1/posts');

        $response->assertErrorStatus([
            'detail' => 'The filter.title field must have a value.',
            'status' => '400',
            'source' => ['parameter' => 'filter.title'],
        ]);
    }

    public function testSearchOne()
    {
        $post = factory(Post::class)->create([
            'slug' => 'my-first-post',
        ]);

        $expected = $this->serialize($post);

        $response = $this
            ->jsonApi('posts')
            ->filter(['slug' => 'my-first-post'])
            ->get('/api/v1/posts');

        $response
            ->assertFetchedOne($expected);
    }

    public function testSearchOneIsNull()
    {
        factory(Post::class)->create(['slug' => 'my-first-post']);

        $response = $this
            ->jsonApi('posts')
            ->filter(['slug' => 'my-second-post'])
            ->get('/api/v1/posts');

        $response
            ->assertFetchedNull();
    }

    /**
     * As the posts adapter uses the `FiltersModel` trait we need to check
     * how it handles unrecognised parameters.
     */
    public function testUnrecognisedFilter()
    {
        $response = $this
            ->jsonApi('posts')
            ->filter(['foo' => 'bar', 'slug' => 'my-first-post'])
            ->get('/api/v1/posts');

        $response
            ->assertStatus(400);
    }

    /**
     * Test searching with included resources.
     */
    public function testSearchWithIncluded()
    {
        $expected = factory(Comment::class, 5)->states('post')->create();

        $response = $this
            ->jsonApi('posts')
            ->includePaths('comments.createdBy')
            ->get('/api/v1/posts');

        $response
            ->assertFetchedMany($expected);
    }

    /**
     * Test that we can search posts for specific ids
     */
    public function testSearchById()
    {
        $models = factory(Post::class, 2)->create();
        // this model should not be in the search results
        $this->createPost();

        $ids = $models->map(fn($model) => $model->getRouteKey());

        $response = $this
            ->jsonApi('posts')
            ->filter(['id' => $ids])
            ->get('/api/v1/posts');

        $response->assertFetchedMany($models);
    }

    /**
     * Test the create resource route.
     */
    public function testCreate()
    {
        $model = $this->createPost(false);

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $model->title,
                'slug' => $model->slug,
                'content' => $model->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $model->author->getRouteKey(),
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $response = $this
            ->withoutExceptionHandling()
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/posts');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/posts'), $expected)
            ->id();

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'title' => $model->title,
            'slug' => $model->slug,
            'content' => $model->content,
            'author_id' => $model->author->getKey(),
        ]);
    }

    public function testCreateInvalid()
    {
        $model = factory(Post::class)->make();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => 1,
                'content' => $model->content,
                'slug' => $model->slug,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $model->author_id,
                    ],
                ],
            ],
        ];

        $expected = [
            [
                'status' => '422',
                'title' => 'Unprocessable Entity',
                'detail' => 'The title must be a string.',
                'source' => [
                    'pointer' => '/data/attributes/title',
                ],
            ],
            [
                'status' => '422',
                'title' => 'Unprocessable Entity',
                'detail' => 'The title must be between 5 and 255 characters.',
                'source' => [
                    'pointer' => '/data/attributes/title',
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/posts');

        $response->assertErrors(422, $expected);
    }

    /**
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/255
     */
    public function testCreateWithoutRequiredMember()
    {
        $model = factory(Post::class)->make();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $model->title,
                'content' => $model->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $model->author_id,
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/posts');

        $response->assertErrorStatus([
            'status' => '422',
            'detail' => 'The slug field is required.',
            'source' => [
                'pointer' => '/data',
            ],
        ]);
    }

    /**
     * Test the read resource route.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/256
     *      we only expect to see the model retrieved once.
     */
    public function testRead()
    {
        $retrieved = 0;

        Post::retrieved(function () use (&$retrieved) {
            $retrieved++;
        });


        $model = $this->createPost();
        $model->tags()->create(['name' => 'Important']);

        $response = $this
            ->withoutExceptionHandling()
            ->jsonApi()
            ->get(url('/api/v1/posts', $model));

        $response->assertFetchedOneExact(
            $this->serialize($model)
        );

        $this->assertSame(1, $retrieved, 'retrieved once');
    }

    /**
     * We must be able to read soft deleted models.
     */
    public function testReadSoftDeleted()
    {
        $post = factory(Post::class)->create(['deleted_at' => Carbon::now()]);

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', $post));

        $response->assertFetchedOneExact(
            $this->serialize($post)
        );
    }

    /**
     * Test reading a resource with included resources. We expect the relationships
     * data identifiers to be serialized in the response so that the compound document
     * has full resource linkage, in accordance with the spec.
     */
    public function testReadWithInclude()
    {
        $model = $this->createPost();
        $tag = $model->tags()->create(['name' => 'Important']);

        $expected = $this->serialize($model);

        $expected['relationships']['author']['data'] = [
            'type' => 'users',
            'id' => (string) $model->author_id,
        ];

        $expected['relationships']['tags']['data'] = [
            ['type' => 'tags', 'id' => $tag->uuid],
        ];

        $expected['relationships']['comments']['data'] = [];

        $response = $this
            ->jsonApi()
            ->includePaths('author', 'tags', 'comments')
            ->get(url('/api/v1/posts', $model));

        $response
            ->assertFetchedOne($expected)
            ->assertIsIncluded('users', $model->author)
            ->assertIsIncluded('tags', $tag);
    }

    /**
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/518
     */
    public function testReadWithEmptyInclude(): void
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->withoutExceptionHandling()
            ->jsonApi()
            ->get("api/v1/posts/{$post->getRouteKey()}?include=");

        $response->assertFetchedOne($this->serialize($post));
    }

    public function testReadWithDefaultInclude(): void
    {
        $mockSchema = $this
            ->getMockBuilder(Schema::class)
            ->onlyMethods(['getIncludePaths'])
            ->setConstructorArgs([$this->app->make(Factory::class)])
            ->getMock();

        $mockSchema->method('getIncludePaths')->willReturn(['author', 'tags', 'comments']);

        $this->app->instance(Schema::class, $mockSchema);

        $model = $this->createPost();
        $tag = $model->tags()->create(['name' => 'Important']);

        $expected = $this->serialize($model);

        $expected['relationships']['author']['data'] = [
            'type' => 'users',
            'id' => (string) $model->author_id,
        ];

        $expected['relationships']['tags']['data'] = [
            ['type' => 'tags', 'id' => $tag->uuid],
        ];

        $expected['relationships']['comments']['data'] = [];

        $response = $this
            ->withoutExceptionHandling()
            ->jsonApi()
            ->get(url('/api/v1/posts', $model));

        $response
            ->assertFetchedOne($expected)
            ->assertIsIncluded('users', $model->author)
            ->assertIsIncluded('tags', $tag);
    }

    /**
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/194
     */
    public function testReadWithInvalidInclude()
    {
        $post = $this->createPost();

        $response = $this
            ->jsonApi()
            ->includePaths('author', 'foo')
            ->get(url('/api/v1/posts', $post));

        $response->assertError(400, [
            'status' => '400',
            'detail' => 'Include path foo is not allowed.',
            'source' => ['parameter' => 'include'],
        ]);
    }

    /**
     * When using camel-case JSON API fields, we may want the relationship URLs
     * to use dash-case for the field name.
     */
    public function testReadWithDashCaseRelationLinks(): void
    {
        $comment = factory(Comment::class)->create();
        $self = 'http://localhost/api/v1/comments/' . $comment->getRouteKey();

        $expected = [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
            'attributes' => [
                'content' => $comment->content,
                'createdAt' => $comment->created_at->toJSON(),
                'updatedAt' => $comment->updated_at->toJSON(),
            ],
            'relationships' => [
                'commentable' => [
                    'links' => [
                        'self' => "{$self}/relationships/commentable",
                        'related' => "{$self}/commentable",
                    ],
                ],
                'createdBy' => [
                    'links' => [
                        'self' => "{$self}/relationships/created-by",
                        'related' => "{$self}/created-by",
                    ],
                ],
            ],
            'links' => [
                'self' => $self,
            ],
        ];

        $response = $this
            ->actingAs($comment->user)
            ->jsonApi()
            ->expects('comments')
            ->get($self);

        $response->assertFetchedOneExact($expected);
    }

    /**
     * Test that the resource can not be found.
     */
    public function testResourceNotFound()
    {
        $response = $this
            ->jsonApi()
            ->get('/api/v1/posts/xyz');

        $response->assertStatus(404);
    }

    /**
     * Test the update resource route.
     */
    public function testUpdate()
    {
        $model = $this->createPost();
        $published = new Carbon('2018-01-01 12:00:00');

        $data = [
            'type' => 'posts',
            'id' => (string) $model->getRouteKey(),
            'attributes' => [
                'slug' => 'posts-test',
                'title' => 'Foo Bar Baz Bat',
                'foo' => 'bar', // attribute that does not exist.
                'published' => $published->toJSON(),
            ],
        ];

        $expected = $data;
        unset($expected['attributes']['foo']);

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $model));

        $response->assertFetchedOne($expected);

        $this->assertDatabaseHas('posts', [
            'id' => $model->getKey(),
            'slug' => 'posts-test',
            'title' => 'Foo Bar Baz Bat',
            'content' => $model->content,
            'published_at' => $published->toDateTimeString(),
        ]);
    }

    /**
     * Issue 125.
     *
     * If the model caches any of its relationships prior to a hydrator being invoked,
     * any changes to that relationship will not be serialized when the schema serializes
     * the model.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/125
     */
    public function testUpdateRefreshes()
    {
        $post = $this->createPost();

        Post::saving(function (Post $saved) {
            $saved->tags; // causes the model to cache the tags relationship.
        });

        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'relationships' => [
                'tags' => [
                    'data' => [
                        ['type' => 'tags', 'id' => $tag->uuid],
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('tags')
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('taggables', [
            'taggable_type' => Post::class,
            'taggable_id' => $post->getKey(),
            'tag_id' => $tag->getKey(),
        ]);
    }

    /**
     * Test that if a client sends a relation that does not exist, it is ignored
     * rather than causing an internal server error.
     */
    public function testUpdateWithUnrecognisedRelationship()
    {
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => 'Hello World',
            ],
            'relationships' => [
                'edited-by' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $post->author_id,
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'title' => 'Hello World',
        ]);
    }

    /**
     * The client sends an unexpected attribute with the same name as a
     * relationship.
     */
    public function testUpdateWithRelationshipAsAttribute()
    {
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => 'Hello World',
                'author' => 'foobar',
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'title' => 'Hello World',
            'author_id' => $post->author_id,
        ]);
    }

    /**
     * Laravel conversion middleware e.g. trim strings, works.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/201
     */
    public function testTrimsStrings()
    {
        $model = $this->createPost();

        $data = [
            'type' => 'posts',
            'id' => (string) $model->getRouteKey(),
            'attributes' => [
                'content' => ' Hello world. ',
            ],
        ];

        $expected = $data;
        $expected['attributes']['content'] = 'Hello world.';

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $model));

        $response->assertFetchedOne($expected);

        $this->assertDatabaseHas('posts', [
            'id' => $model->getKey(),
            'content' => 'Hello world.',
        ]);
    }

    public function testInvalidDateTime()
    {
        $model = $this->createPost();

        $data = [
            'type' => 'posts',
            'id' => (string) $model->getRouteKey(),
            'attributes' => [
                'published' => '2018-08-08',
            ],
        ];

        $expected = [
            'status' => '422',
            'detail' => 'The published date is not a valid ISO 8601 date and time.',
            'source' => [
                'pointer' => '/data/attributes/published',
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $model));

        $response->assertErrorStatus($expected);
    }

    public function testSoftDelete()
    {
        Event::fake();

        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'deletedAt' => (new Carbon('2018-01-01 12:00:00'))->toJSON(),
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);
        $this->assertSoftDeleted('posts', [$post->getKeyName() => $post->getKey()]);

        Event::assertDispatched("eloquent.deleting: " . Post::class, function ($name, $actual) use ($post) {
            return $post->is($actual);
        });

        Event::assertDispatched("eloquent.deleted: " . Post::class, function ($name, $actual) use ($post) {
            return $post->is($actual);
        });

        Event::assertNotDispatched("eloquent.forceDeleted: " . Post::class);
    }

    public function testSoftDeleteWithBoolean()
    {
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'deletedAt' => true,
            ],
        ];

        $expected = $data;
        $expected['attributes']['deletedAt'] = Carbon::now()->toJSON();

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($expected);
        $this->assertSoftDeleted('posts', [$post->getKeyName() => $post->getKey()]);
    }

    /**
     * Test that we can update attributes at the same time as soft deleting.
     */
    public function testUpdateAndSoftDelete()
    {
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'deletedAt' => (new Carbon('2018-01-01 12:00:00'))->toJSON(),
                'title' => 'My Post Is Soft Deleted',
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('posts', [
            $post->getKeyName() => $post->getKey(),
            'title' => 'My Post Is Soft Deleted',
        ]);
    }

    public function testRestore()
    {
        Event::fake();

        $post = factory(Post::class)->create(['deleted_at' => '2018-01-01 12:00:00']);

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'deletedAt' => null,
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('posts', [
            $post->getKeyName() => $post->getKey(),
            'deleted_at' => null,
        ]);

        Event::assertDispatched("eloquent.restored: " . Post::class, function ($name, $actual) use ($post) {
            return $post->is($actual);
        });
    }

    public function testRestoreWithBoolean()
    {
        Event::fake();

        $post = factory(Post::class)->create(['deleted_at' => '2018-01-01 12:00:00']);

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'deletedAt' => false,
            ],
        ];

        $expected = $data;
        $expected['attributes']['deletedAt'] = null;

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($expected);

        $this->assertDatabaseHas('posts', [
            $post->getKeyName() => $post->getKey(),
            'deleted_at' => null,
        ]);

        Event::assertDispatched("eloquent.restored: " . Post::class, function ($name, $actual) use ($post) {
            return $post->is($actual);
        });
    }

    /**
     * Test that we can update attributes at the same time as restoring the model.
     */
    public function testUpdateAndRestore()
    {
        Event::fake();

        $post = factory(Post::class)->create(['deleted_at' => '2018-01-01 12:00:00']);

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'deletedAt' => null,
                'title' => 'My Post Is Restored',
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('posts', [
            $post->getKeyName() => $post->getKey(),
            'deleted_at' => null,
            'title' => 'My Post Is Restored',
        ]);

        Event::assertDispatched("eloquent.restored: " . Post::class, function ($name, $actual) use ($post) {
            return $post->is($actual);
        });
    }

    /**
     * Test the delete resource route.
     */
    public function testDelete()
    {
        Event::fake();

        $post = $this->createPost();

        $response = $this
            ->jsonApi()
            ->delete(url('/api/v1/posts', $post));

        $response->assertNoContent();
        $this->assertDatabaseMissing('posts', [$post->getKeyName() => $post->getKey()]);

        Event::assertDispatched("eloquent.deleting: " . Post::class, function ($name, $actual) use ($post) {
            return $post->is($actual);
        });

        Event::assertDispatched("eloquent.deleted: " . Post::class, function ($name, $actual) use ($post) {
            return $post->is($actual);
        });

        Event::assertDispatched("eloquent.forceDeleted: " . Post::class, function ($name, $actual) use ($post) {
            return $post->is($actual);
        });
    }

    /**
     * Test that the delete request is logically validated.
     */
    public function testCannotDeletePostHasComments()
    {
        $post = factory(Comment::class)->states('post')->create()->commentable;

        $expected = [
            'title' => 'Not Deletable',
            'status' => '422',
            'detail' => 'Cannot delete a post with comments.',
        ];

        $response = $this
            ->jsonApi()
            ->delete(url('/api/v1/posts', $post));

        $response->assertExactErrorStatus($expected);
    }

    /**
     * Just a helper method so that we get a type-hinted model back...
     *
     * @param bool $create
     * @return Post
     */
    private function createPost($create = true)
    {
        $builder = factory(Post::class);

        return $create ? $builder->create() : $builder->make();
    }

    /**
     * Get the posts resource that we expect in server responses.
     *
     * @param Post $post
     * @return array
     */
    private function serialize(Post $post)
    {
        $self = url('/api/v1/posts', [$post]);

        return [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'content' => $post->content,
                'createdAt' => $post->created_at->toJSON(),
                'deletedAt' => optional($post->deleted_at)->toJSON(),
                'published' => optional($post->published_at)->toJSON(),
                'slug' => $post->slug,
                'title' => $post->title,
                'updatedAt' => $post->updated_at->toJSON(),
            ],
            'relationships' => [
                'author' => [
                    'links' => [
                        'self' => "$self/relationships/author",
                        'related' => "$self/author",
                    ],
                ],
                'comments' => [
                    'links' => [
                        'self' => "$self/relationships/comments",
                        'related' => "$self/comments",
                    ],
                    'meta' => [
                        'count' => $post->comments()->count(),
                    ],
                ],
                'image' => [
                    'links' => [
                        'self' => "$self/relationships/image",
                        'related' => "$self/image",
                    ],
                ],
                'tags' => [
                    'links' => [
                        'self' => "$self/relationships/tags",
                        'related' => "$self/tags",
                    ],
                ],
            ],
            'links' => [
                'self' => $self,
            ],
        ];
    }
}
