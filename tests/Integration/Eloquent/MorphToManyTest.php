<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use CloudCreativity\LaravelJsonApi\Tests\Models\Tag;

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

    /**
     * @var string
     */
    protected $resourceType = 'posts';

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

        $this->doCreate($data)->assertCreatedWithId($data);

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
                            'id' => (string) $tag->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $id = $this
            ->doCreate($data)
            ->assertCreatedWithId($data);

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
                            'id' => (string) $tags->first()->getKey(),
                        ],
                        [
                            'type' => 'tags',
                            'id' => (string) $tags->last()->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $id = $this
            ->doCreate($data)
            ->assertCreatedWithId($data);

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
            'id' => (string) $post->getKey(),
            'relationships' => [
                'tags' => [
                    'data' => [],
                ],
            ],
        ];

        $this->doUpdate($data)->assertUpdated($data);

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
            'id' => (string) $post->getKey(),
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
                            'id' => (string) $tag->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $this->doUpdate($data)->assertUpdated($data);
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
            'id' => (string) $post->getKey(),
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
                            'id' => (string) $tags->first()->getKey(),
                        ],
                        [
                            'type' => 'tags',
                            'id' => (string) $tags->last()->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $this->doUpdate($data)->assertUpdated($data);
        $this->assertTagsAre($post, $tags);
    }

    public function testReadRelated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 2)->create();

        $post->tags()->sync($tags);

        $this->doReadRelated($post, 'tags')
            ->assertReadHasMany('tags', $tags);
    }

    public function testReadRelatedEmpty()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();

        $this->doReadRelated($post, 'tags')
            ->assertReadHasMany(null);
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 2)->create();
        $post->tags()->sync($tags);

        $this->doReadRelationship($post, 'tags')
            ->assertReadHasManyIdentifiers('tags', $tags);
    }

    public function testReadEmptyRelationship()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelationship($post, 'tags')
            ->assertReadHasManyIdentifiers(null);
    }

    public function testReplaceEmptyRelationshipWithRelatedResource()
    {
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 2)->create();

        $data = $tags->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => (string) $tag->getKey()];
        })->all();

        $this->doReplaceRelationship($post, 'tags', $data)
            ->assertStatus(204);

        $this->assertTagsAre($post, $tags);
    }

    public function testReplaceRelationshipWithNone()
    {
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 2)->create();
        $post->tags()->sync($tags);

        $this->expectSuccess()
            ->doReplaceRelationship($post, 'tags', [])
            ->assertStatus(204);

        $this->assertFalse($post->tags()->exists());
    }

    public function testReplaceRelationshipWithDifferentResources()
    {
        $post = factory(Post::class)->create();
        $post->tags()->sync(factory(Tag::class, 2)->create());

        $tags = factory(Tag::class, 3)->create();

        $data = $tags->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => (string) $tag->getKey()];
        })->all();

        $this->expectSuccess()
            ->doReplaceRelationship($post, 'tags', $data)
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
            return ['type' => 'tags', 'id' => (string) $tag->getKey()];
        })->all();

        $this->expectSuccess()
            ->doAddToRelationship($post, 'tags', $data)
            ->assertStatus(204);

        $this->assertTagsAre($post, $existing->merge($add));
    }

    public function testRemoveFromRelationship()
    {
        $post = factory(Post::class)->create();
        $tags = factory(Tag::class, 4)->create();
        $post->tags()->sync($tags);

        $data = $tags->take(2)->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => (string) $tag->getKey()];
        })->all();

        $this->expectSuccess()
            ->doRemoveFromRelationship($post, 'tags', $data)
            ->assertStatus(204);

        $this->assertTagsAre($post, [$tags->get(2), $tags->get(3)]);
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
