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
use Composer\Semver\Semver;
use DummyApp\Comment;
use DummyApp\Post;
use DummyApp\Tag;
use Illuminate\Support\Facades\Event;

class ResourceTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

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

        $this->doSearch(['sort' => '-title'])
            ->assertFetchedManyInOrder([$b, $a]);
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

        $this->doSearch(['filter' => ['title' => 'My']])
            ->assertFetchedManyInOrder([$a, $b]);
    }

    public function testInvalidFilter()
    {
        $this->doSearch(['filter' => ['title' => '']])->assertError(400, [
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

        $this->doSearch(['filter' => ['slug' => 'my-first-post']])
            ->assertFetchedOne($expected);
    }

    public function testSearchOneIsNull()
    {
        factory(Post::class)->create(['slug' => 'my-first-post']);

        $this->doSearch(['filter' => ['slug' => 'my-second-post']])
            ->assertFetchedNull();
    }

    /**
     * As the posts adapter uses the `FiltersModel` trait we need to check
     * how it handles unrecognised parameters.
     */
    public function testUnrecognisedFilter()
    {
        $this->doSearch(['filter' => ['foo' => 'bar', 'slug' => 'my-first-post']])
            ->assertStatus(400);
    }

    /**
     * Test searching with included resources.
     */
    public function testSearchWithIncluded()
    {
        $expected = factory(Comment::class, 5)->states('post')->create();

        $this->doSearch(['include' => 'comments.created-by'])
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

        $this->doSearchById($models)->assertFetchedMany($models);
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
                        'id' => (string) $model->author_id,
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $id = $this
            ->doCreate($data)
            ->assertCreatedWithServerId(url('/api/v1/posts'), $expected)
            ->id();

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'title' => $model->title,
            'slug' => $model->slug,
            'content' => $model->content,
            'author_id' => $model->author_id,
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

        $this->doCreate($data)->assertErrors(422, $expected);
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

        $this->doCreate($data)->assertErrorStatus([
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

        $this->doRead($model)->assertFetchedOneExact(
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

        $this->doRead($post)->assertFetchedOneExact(
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

        $this->doRead($model, ['include' => 'author,tags,comments'])
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

        $this->doRead($post, ['include' => 'author,foo'])->assertError(400, [
            'status' => '400',
            'detail' => 'Include path foo is not allowed.',
            'source' => ['parameter' => 'include'],
        ]);
    }

    /**
     * Test that the resource can not be found.
     */
    public function testResourceNotFound()
    {
        $this->doRead('xyz')->assertStatus(404);
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
                'published' => $published->toW3cString(),
            ],
        ];

        $expected = $data;
        unset($expected['attributes']['foo']);

        $this->doUpdate($data)->assertUpdated($expected);

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

        $this->doUpdate($data, ['include' => 'tags'])->assertUpdated($data);

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

        $this->doUpdate($data)->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'title' => 'Hello World',
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

        $this->doUpdate($data)->assertUpdated($expected);

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

        $this->doUpdate($data)->assertErrorStatus($expected);
    }

    public function testSoftDelete()
    {
        Event::fake();

        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'deleted-at' => (new Carbon('2018-01-01 12:00:00'))->toAtomString(),
            ],
        ];

        $this->doUpdate($data)->assertUpdated($data);
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
                'deleted-at' => true,
            ],
        ];

        $expected = $data;
        $expected['attributes']['deleted-at'] = Carbon::now()->toAtomString();

        $this->doUpdate($data)->assertUpdated($expected);
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
                'deleted-at' => (new Carbon('2018-01-01 12:00:00'))->toAtomString(),
                'title' => 'My Post Is Soft Deleted',
            ],
        ];

        $this->doUpdate($data)->assertUpdated($data);

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
                'deleted-at' => null,
            ],
        ];

        $this->doUpdate($data)->assertUpdated($data);

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
                'deleted-at' => false,
            ],
        ];

        $expected = $data;
        $expected['attributes']['deleted-at'] = null;

        $this->doUpdate($data)->assertUpdated($expected);

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
                'deleted-at' => null,
                'title' => 'My Post Is Restored',
            ],
        ];

        $this->doUpdate($data)->assertUpdated($data);

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

        $this->doDelete($post)->assertDeleted();
        $this->assertDatabaseMissing('posts', [$post->getKeyName() => $post->getKey()]);

        Event::assertDispatched("eloquent.deleting: " . Post::class, function ($name, $actual) use ($post) {
            return $post->is($actual);
        });

        Event::assertDispatched("eloquent.deleted: " . Post::class, function ($name, $actual) use ($post) {
            return $post->is($actual);
        });

        /**
         * Force deleted event was added in Laravel 5.6.
         */
        if (Semver::satisfies($this->app->version(), '>=5.6')) {
            Event::assertDispatched("eloquent.forceDeleted: " . Post::class, function ($name, $actual) use ($post) {
                return $post->is($actual);
            });
        }
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

        $this->doDelete($post)->assertExactErrorStatus($expected);
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
                'created-at' => $post->created_at->toAtomString(),
                'deleted-at' => $post->deleted_at ? $post->deleted_at->toAtomString() : null,
                'published' => $post->published_at ? $post->published_at->toAtomString() : null,
                'slug' => $post->slug,
                'title' => $post->title,
                'updated-at' => $post->updated_at->toAtomString(),
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
