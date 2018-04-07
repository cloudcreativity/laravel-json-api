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

use Carbon\Carbon;
use DummyApp\Comment;
use DummyApp\Events\ResourceEvent;
use DummyApp\Http\Controllers\PostsController;
use DummyApp\Post;
use DummyApp\Tag;

class ResourceTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

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

        $response = $this->doSearch(['sort' => '-title']);
        $response->assertSearchResponse()->assertContainsExact([
            ['type' => 'posts', 'id' => $b->getKey()],
            ['type' => 'posts', 'id' => $a->getKey()],
        ]);
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
            ->assertSearchResponse()->assertContainsOnly(['posts' => [$a->getKey(), $b->getKey()]]);
    }

    public function testInvalidFilter()
    {
        $this->doSearch(['filter' => ['title' => '']])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('filter.title');
    }

    public function testSearchOne()
    {
        $post = factory(Post::class)->create([
            'slug' => 'my-first-post',
        ]);

        $expected = $this->serialize($post);

        $this->doSearch(['filter' => ['slug' => 'my-first-post']])
            ->assertReadHasOne($expected);
    }

    public function testSearchOneIsNull()
    {
        factory(Post::class)->create(['slug' => 'my-first-post']);

        $this->doSearch(['filter' => ['slug' => 'my-second-post']])
            ->assertReadHasOne(null);
    }

    /**
     * Test searching with included resources.
     */
    public function testSearchWithIncluded()
    {
        factory(Comment::class, 5)->states('post')->create();

        $this->doSearch(['include' => 'comments.created-by'])
            ->assertSearchedMany();
    }

    /**
     * Test that we can search posts for specific ids
     */
    public function testSearchById()
    {
        $models = factory(Post::class, 2)->create();
        // this model should not be in the search results
        $this->createPost();

        $this
            ->doSearchById($models)
            ->assertSearchByIdResponse($models);
    }

    /**
     * Test the create resource route.
     */
    public function testCreate()
    {
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();
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

        $id = $this
            ->doCreate($data)
            ->assertCreateResponse($data);

        $this->assertModelCreated($model, $id, ['title', 'slug', 'content', 'author_id']);
    }

    /**
     * Test the read resource route.
     */
    public function testRead()
    {
        $model = $this->createPost();
        $model->tags()->create(['name' => 'Important']);

        $this->doRead($model)->assertReadResponse($this->serialize($model));
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
            'id' => (string) $model->getKey(),
            'attributes' => [
                'slug' => 'posts-test',
                'title' => 'Foo Bar Baz Bat',
                'foo' => 'bar', // attribute that does not exist.
                'published' => $published->toW3cString(),
            ],
        ];

        $expected = $data;
        unset($expected['attributes']['foo']);

        $this->doUpdate($data)->assertUpdateResponse($expected);

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
        /** @var PostsController $controller */
        $controller = $this->app->make(PostsController::class);
        $this->app->instance(PostsController::class, $controller);

        app('events')->listen(ResourceEvent::class, function ($event) {
            if ('saving' === $event->hook) {
                $event->record->tags; // causes the model to cache the tags relationship.
            }
        });

        $model = $this->createPost();
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $model->getKey(),
            'relationships' => [
                'tags' => [
                    'data' => [
                        ['type' => 'tags', 'id' => (string) $tag->getKey()],
                    ],
                ],
            ],
        ];

        $this->doUpdate($data)->assertUpdateResponse($data);

        $this->assertDatabaseHas('taggables', [
            'taggable_type' => Post::class,
            'taggable_id' => $model->getKey(),
            'tag_id' => $tag->getKey(),
        ]);
    }

    /**
     * Test the delete resource route.
     */
    public function testDelete()
    {
        $model = $this->createPost();

        $this->doDelete($model)->assertDeleteResponse();
        $this->assertModelDeleted($model);
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
     * @param Post $model
     * @return array
     */
    private function serialize(Post $model)
    {
        return [
            'type' => 'posts',
            'id' => $id = (string) $model->getKey(),
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
                'tags' => [
                    'data' => $model->tags->map(function (Tag $tag) {
                        return ['type' => 'tags', 'id' => (string) $tag->getKey()];
                    })->all(),
                ],
                'comments' => [
                    'links' => [
                        'self' => "http://localhost/api/v1/posts/$id/relationships/comments",
                        'related' => "http://localhost/api/v1/posts/$id/comments",
                    ],
                ],
            ],
        ];
    }
}
