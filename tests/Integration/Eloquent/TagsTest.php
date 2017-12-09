<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use CloudCreativity\LaravelJsonApi\Tests\Models\Tag;
use CloudCreativity\LaravelJsonApi\Tests\Models\Video;

class TagsTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'tags';

    public function testReadTaggables()
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
}
