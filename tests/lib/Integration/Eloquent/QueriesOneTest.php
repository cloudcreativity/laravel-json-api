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

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Post;
use DummyApp\Tag;
use DummyApp\Video;

class QueriesOneTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    public function testRelated()
    {
        $tag = factory(Tag::class)->create();
        $post = factory(Post::class)->create();
        $post->tags()->sync($tag);

        /** @var Video $video */
        $video = factory(Video::class, 3)->create()->each(function (Video $video) use ($tag) {
            $video->tags()->sync($tag);
        })->first();

        factory(Video::class, 2)->create();

        $expected = [
            'type' => 'videos',
            'id' => $video->getRouteKey(),
            'attributes' => [
                'title' => $video->title,
            ],
        ];

        $this->doReadRelated($post, 'related-video')
            ->assertFetchedOne($expected);
    }

    public function testRelationship()
    {
        $tag = factory(Tag::class)->create();
        $post = factory(Post::class)->create();
        $post->tags()->sync($tag);

        /** @var Video $video */
        $video = factory(Video::class, 3)->create()->each(function (Video $video) use ($tag) {
            $video->tags()->sync($tag);
        })->first();

        factory(Video::class, 2)->create();

        $this->doReadRelationship($post, 'related-video')
            ->willSeeType('videos')
            ->assertFetchedToOne($video);
    }
}
