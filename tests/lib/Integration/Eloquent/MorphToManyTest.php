<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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
use DummyApp\Post;
use DummyApp\Tag;

/**
 * Class HasManyTest
 *
 * Test a JSON API has-many relationship that relates to an Eloquent
 * morph-to-many relationship.
 *
 * In our dummy app, this is the tags relationship on the post model.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MorphToManyTest extends TestCase
{

    public function testCreateWithEmpty()
    {
        /** @var Post $post */
        $post = factory(Post::class)->make([
            'author_id' => null,
        ]);

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'tags' => [
                    'data' => [],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('tags')
            ->post('/api/v1/posts');

        $response
            ->assertCreatedWithServerId(url('/api/v1/posts'), $data);

        $this->assertDatabaseMissing('taggables', [
            'taggable_type' => Post::class,
        ]);
    }

    public function testCreateWithRelated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->make();
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'tags' => [
                    'data' => [
                        [
                            'type' => 'tags',
                            'id' => $tag->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('tags')
            ->post('/api/v1/posts');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/posts'), $data)
            ->id();

        $this->assertTagIs(Post::find($id), $tag);
    }

    public function testCreateWithManyRelated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->make();
        $tags = factory(Tag::class, 2)->create();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'tags' => [
                    'data' => [
                        [
                            'type' => 'tags',
                            'id' => $tags->first()->getRouteKey(),
                        ],
                        [
                            'type' => 'tags',
                            'id' => $tags->last()->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('tags')
            ->post('/api/v1/posts');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/posts'), $data)
            ->id();

        $this->assertTagsAre(Post::find($id), $tags);
    }

    public function testUpdateReplacesRelationshipWithEmptyRelationship()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 2)->create();
        $post->tags()->sync($tags);

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'relationships' => [
                'tags' => [
                    'data' => [],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('tags')
            ->patch(url('/api/v1/posts', $post));

        $response->assertFetchedOne($data);

        $this->assertDatabaseMissing('taggables', [
            'taggable_type' => Post::class,
        ]);
    }

    public function testUpdateReplacesEmptyRelationshipWithResource()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        $tag = factory(Tag::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'tags' => [
                    'data' => [
                        [
                            'type' => 'tags',
                            'id' => $tag->getRouteKey(),
                        ],
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
        $this->assertTagIs($post, $tag);
    }

    public function testUpdateChangesRelatedResources()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        $post->tags()->sync(factory(Tag::class, 3)->create());

        $tags = factory(Tag::class, 2)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'tags' => [
                    'data' => [
                        [
                            'type' => 'tags',
                            'id' => $tags->first()->getRouteKey(),
                        ],
                        [
                            'type' => 'tags',
                            'id' => $tags->last()->getRouteKey(),
                        ],
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
        $this->assertTagsAre($post, $tags);
    }

    public function testReadRelated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 2)->create();
        $expected = $tags->sortBy('name');

        $post->tags()->sync($tags);

        $response = $this
            ->jsonApi('tags')
            ->get(url('/api/v1/posts', [$post, 'tags']));

        $response
            ->assertFetchedMany($expected);
    }

    public function testReadRelatedEmpty()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi('tags')
            ->get(url('/api/v1/posts', [$post, 'tags']));

        $response
            ->assertFetchedNone();
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 2)->create();
        $post->tags()->sync($tags);

        $expected = $tags->sortBy('name')->map(function (Tag $tag) {
            return $tag->getRouteKey();
        });

        $response = $this
            ->jsonApi('tags')
            ->get(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response
            ->assertFetchedToMany($expected);
    }

    public function testReadEmptyRelationship()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi('tags')
            ->get(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response
            ->assertFetchedNone();
    }

    public function testReplaceEmptyRelationshipWithRelatedResource()
    {
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 2)->create();

        $data = $tags->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => $tag->getRouteKey()];
        })->all();

        $response = $this
            ->jsonApi('tags')
            ->withData($data)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response
            ->assertStatus(204);

        $this->assertTagsAre($post, $tags);
    }

    public function testReplaceRelationshipWithNone()
    {
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 2)->create();
        $post->tags()->sync($tags);

        $response = $this
            ->jsonApi('tags')
            ->withData([])
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response
            ->assertStatus(204);

        $this->assertFalse($post->tags()->exists());
    }

    public function testReplaceRelationshipWithDifferentResources()
    {
        $post = factory(Post::class)->create();
        $post->tags()->sync(factory(Tag::class, 2)->create());

        $tags = factory(Tag::class, 3)->create();

        $data = $tags->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => $tag->getRouteKey()];
        })->all();

        $response = $this
            ->jsonApi('tags')
            ->withData($data)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response
            ->assertStatus(204);

        $this->assertTagsAre($post, $tags);
    }

    public function testAddToRelationship()
    {
        $post = factory(Post::class)->create();
        $existing = factory(Tag::class, 2)->create();
        $post->tags()->sync($existing);

        $add = factory(Tag::class, 2)->create();
        $data = $add->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => $tag->getRouteKey()];
        })->all();

        $response = $this
            ->jsonApi('tags')
            ->withData($data)
            ->post(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response
            ->assertStatus(204);

        $this->assertTagsAre($post, $existing->merge($add));
    }

    /**
     * From the spec:
     *
     * > If a client makes a POST request to a URL from a relationship link,
     * > the server MUST add the specified members to the relationship unless
     * > they are already present. If a given type and id is already in the
     * > relationship, the server MUST NOT add it again.
     */
    public function testAddToRelationshipDoesNotCreateDuplicates()
    {
        $post = factory(Post::class)->create();
        $existing = factory(Tag::class, 2)->create();
        $post->tags()->sync($existing);

        $add = factory(Tag::class, 2)->create();
        $data = $add->merge($existing)->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => $tag->getRouteKey()];
        })->all();

        $response = $this
            ->jsonApi('tags')
            ->withData($data)
            ->post(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response
            ->assertStatus(204);

        $this->assertTagsAre($post, $existing->merge($add));
    }

    public function testRemoveFromRelationship()
    {
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 4)->create();
        $post->tags()->sync($tags);

        $data = $tags->take(2)->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => $tag->getRouteKey()];
        })->all();

        $response = $this
            ->jsonApi('tags')
            ->withData($data)
            ->delete(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response
            ->assertStatus(204);

        $this->assertTagsAre($post, [$tags->get(2), $tags->get(3)]);
    }

    /**
     * From the spec:
     *
     * > If all of the specified resources are able to be removed from,
     * > or are already missing from, the relationship then the server
     * > MUST return a successful response.
     */
    public function testRemoveWithIdsThatAreNotRelated()
    {
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 2)->create();
        $post->tags()->sync($tags);

        $data = factory(Tag::class, 2)->create()->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => $tag->getRouteKey()];
        })->all();

        $response = $this
            ->jsonApi('tags')
            ->withData($data)
            ->delete(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response
            ->assertStatus(204);

        $this->assertTagsAre($post, $tags);
    }

    /**
     * @param $post
     * @param $tag
     * @return void
     */
    private function assertTagIs(Post $post, Tag $tag)
    {
        $this->assertTagsAre($post, [$tag]);
    }

    /**
     * @param Post $post
     * @param iterable $tags
     * @return void
     */
    private function assertTagsAre(Post $post, $tags)
    {
        $this->assertSame(count($tags), $post->tags()->count());

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('taggables', [
                'taggable_type' => Post::class,
                'taggable_id' => $post->getKey(),
                'tag_id' => $tag->getKey(),
            ]);
        }
    }
}
