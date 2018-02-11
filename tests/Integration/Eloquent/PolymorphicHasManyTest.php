<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use CloudCreativity\LaravelJsonApi\Tests\Models\Tag;
use CloudCreativity\LaravelJsonApi\Tests\Models\Video;

/**
 * Class PolymorphicHasManyTest
 *
 * Test a JSON API has-many relationship that can hold more than one type
 * of resource.
 *
 * In our dummy app, this is the taggables relationship on our tags resource.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class PolymorphicHasManyTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'tags';

    public function testCreateWithEmpty()
    {
        $tag = factory(Tag::class)->make();

        $data = [
            'type' => 'tags',
            'attributes' => [
                'name' => $tag->name,
            ],
            'relationships' => [
                'taggables' => [
                    'data' => [],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $id = $this->expectSuccess()->doCreate($data)->assertCreatedWithId($expected);

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $id,
        ]);
    }

    public function testCreateWithRelated()
    {
        $tag = factory(Tag::class)->make();
        $post = factory(Post::class)->create();
        $videos = factory(Video::class, 2)->create();

        $data = [
            'type' => 'tags',
            'attributes' => [
                'name' => $tag->name,
            ],
            'relationships' => [
                'taggables' => [
                    'data' => [
                        [
                            'type' => 'videos',
                            'id' => (string) $videos->first()->getKey(),
                        ],
                        [
                            'type' => 'posts',
                            'id' => (string) $post->getKey(),
                        ],
                        [
                            'type' => 'videos',
                            'id' => (string) $videos->last()->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $id = $this->expectSuccess()->doCreate($data)->assertCreatedWithId($expected);
        $tag = Tag::findOrFail($id);

        $this->assertTaggablesAre($tag, [$post], $videos);
    }

    public function testUpdateReplacesRelationshipWithEmptyRelationship()
    {
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();
        $tag->posts()->saveMany(factory(Post::class, 2)->create());
        $tag->videos()->save(factory(Video::class)->create());

        $data = [
            'type' => 'tags',
            'id' => (string) $tag->getKey(),
            'relationships' => [
                'taggables' => [
                    'data' => [],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $this->doUpdate($data)->assertUpdated($expected);

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag->getKey(),
        ]);
    }

    public function testUpdateReplacesEmptyRelationshipWithResource()
    {
        $tag = factory(Tag::class)->create();
        $video = factory(Video::class)->create();

        $data = [
            'type' => 'tags',
            'id' => (string) $tag->getKey(),
            'relationships' => [
                'taggables' => [
                    'data' => [
                        [
                            'type' => 'videos',
                            'id' => (string) $video->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $this->doUpdate($data)->assertUpdated($expected);

        $this->assertTaggablesAre($tag, [], [$video]);
    }

    public function testUpdateReplacesEmptyRelationshipWithResources()
    {
        $tag = factory(Tag::class)->create();
        $post = factory(Post::class)->create();
        $video = factory(Video::class)->create();

        $data = [
            'type' => 'tags',
            'id' => (string) $tag->getKey(),
            'relationships' => [
                'taggables' => [
                    'data' => [
                        [
                            'type' => 'posts',
                            'id' => (string) $post->getKey(),
                        ],
                        [
                            'type' => 'videos',
                            'id' => (string) $video->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $this->doUpdate($data)->assertUpdated($expected);

        $this->assertTaggablesAre($tag, [$post], [$video]);
    }

    public function testReadRelated()
    {
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();
        $tag->posts()->sync($post = factory(Post::class)->create());
        $tag->videos()->sync($videos = factory(Video::class, 2)->create());

        $this->expectSuccess()
            ->doReadRelated($tag, 'taggables')
            ->assertReadPolymorphHasMany([
                'posts' => $post,
                'videos' => $videos,
            ]);
    }

    public function testReadEmptyRelated()
    {
        $tag = factory(Tag::class)->create();

        $this->doReadRelated($tag, 'taggables')
            ->assertReadPolymorphHasMany([]);
    }

    public function testReadRelationship()
    {
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();
        $tag->posts()->sync($post = factory(Post::class)->create());
        $tag->videos()->sync($videos = factory(Video::class, 2)->create());

        $this->expectSuccess()
            ->doReadRelated($tag, 'taggables')
            ->assertReadPolymorphHasManyIdentifiers([
                'posts' => $post,
                'videos' => $videos,
            ]);
    }

    public function testReadEmptyRelationship()
    {
        $tag = factory(Tag::class)->create();

        $this->doReadRelated($tag, 'taggables')
            ->assertReadPolymorphHasManyIdentifiers([]);
    }

    public function testReplaceEmptyRelationshipWithRelatedResources()
    {
        $tag = factory(Tag::class)->create();
        $post = factory(Post::class)->create();
        $video = factory(Video::class)->create();

        $this->doReplaceRelationship($tag, 'taggables', [
            [
                'type' => 'videos',
                'id' => (string) $video->getKey(),
            ],
            [
                'type' => 'posts',
                'id' => (string) $post->getKey(),
            ],
        ])->assertStatus(204);

        $this->assertTaggablesAre($tag, [$post], [$video]);
    }

    public function testReplaceRelationshipWithNone()
    {
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();
        $tag->videos()->attach(factory(Video::class)->create());

        $this->doReplaceRelationship($tag, 'taggables', [])
            ->assertStatus(204);

        $this->assertNoTaggables($tag);
    }

    public function testReplaceRelationshipWithDifferentResources()
    {
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();
        $tag->posts()->attach(factory(Post::class)->create());
        $tag->videos()->attach(factory(Video::class)->create());

        $posts = factory(Post::class, 2)->create();
        $video = factory(Video::class)->create();

        $this->doReplaceRelationship($tag, 'taggables', [
            [
                'type' => 'posts',
                'id' => (string) $posts->last()->getKey(),
            ],
            [
                'type' => 'posts',
                'id' => (string) $posts->first()->getKey(),
            ],
            [
                'type' => 'videos',
                'id' => (string) $video->getKey(),
            ],
        ])->assertStatus(204);

        $this->assertTaggablesAre($tag, $posts, [$video]);
    }

    public function testAddToRelationship()
    {
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();
        $tag->posts()->attach($existingPost = factory(Post::class)->create());
        $tag->videos()->attach($existingVideo = factory(Video::class)->create());

        $posts = factory(Post::class, 2)->create();
        $video = factory(Video::class)->create();

        $this->doAddToRelationship($tag, 'taggables', [
            [
                'type' => 'posts',
                'id' => (string) $posts->last()->getKey(),
            ],
            [
                'type' => 'posts',
                'id' => (string) $posts->first()->getKey(),
            ],
            [
                'type' => 'videos',
                'id' => (string) $video->getKey(),
            ],
        ])->assertStatus(204);

        $this->assertTaggablesAre($tag, $posts->push($existingPost), [$existingVideo, $video]);
    }

    public function testRemoveFromRelationship()
    {
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();
        $tag->posts()->saveMany($allPosts = factory(Post::class, 3)->create());
        $tag->videos()->saveMany($allVideos = factory(Video::class, 3)->create());

        /** @var Post $post1 */
        $post1 = $allPosts->first();
        /** @var Post $post2 */
        $post2 = $allPosts->last();
        /** @var Video $video */
        $video = $allVideos->last();

        $this->doRemoveFromRelationship($tag, 'taggables', [
            [
                'type' => 'posts',
                'id' => (string) $post1->getKey(),
            ],
            [
                'type' => 'posts',
                'id' => (string) $post2->getKey(),
            ],
            [
                'type' => 'videos',
                'id' => (string) $video->getKey(),
            ],
        ])->assertStatus(204);

        $this->assertTaggablesAre(
            $tag,
            [$allPosts->get(1)],
            [$allVideos->first(), $allVideos->get(1)]
        );
    }

    /**
     * @param Tag $tag
     * @return void
     */
    private function assertNoTaggables(Tag $tag)
    {
        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag->getKey(),
        ]);
    }

    /**
     * @param Tag $tag
     * @param iterable $posts
     * @param iterable $videos
     * @return void
     */
    private function assertTaggablesAre(Tag $tag, $posts, $videos)
    {
        $this->assertSame(
            count($posts) + count($videos),
            \DB::table('taggables')->where('tag_id', $tag->getKey())->count(),
            'Unexpected number of taggables.'
        );

        $this->assertSame(count($posts), $tag->posts()->count(), 'Unexpected number of posts.');
        $this->assertSame(count($videos), $tag->videos()->count(), 'Unexpected number of videos.');

        /** @var Post $post */
        foreach ($posts as $post) {
            $this->assertDatabaseHas('taggables', [
                'taggable_type' => Post::class,
                'taggable_id' => $post->getKey(),
                'tag_id' => $tag->getKey(),
            ]);
        }

        /** @var Video $video */
        foreach ($videos as $video) {
            $this->assertDatabaseHas('taggables', [
                'taggable_type' => Video::class,
                'taggable_id' => $video->getKey(),
                'tag_id' => $tag->getKey(),
            ]);
        }
    }
}
