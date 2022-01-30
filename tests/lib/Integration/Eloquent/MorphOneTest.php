<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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
use DummyApp\Image;
use DummyApp\Post;

/**
 * Class MorphOneTest
 *
 * Tests a JSON API has-one relationship that relates to an Eloquent morph-one
 * relationship.
 *
 * In our dummy app, this is the image relationship on a posts model.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MorphOneTest extends TestCase
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
        $post = factory(Post::class)->make();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'content' => $post->content,
                'slug' => $post->slug,
            ],
            'relationships' => [
                'image' => [
                    'data' => null,
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('image')
            ->post('/api/v1/posts');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/posts'), $data)
            ->id();

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'title' => $data['attributes']['title'],
        ]);
    }

    public function testCreateWithRelated()
    {
        $post = factory(Post::class)->make();
        $image = factory(Image::class)->create();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'content' => $post->content,
                'slug' => $post->slug,
            ],
            'relationships' => [
                'image' => [
                    'data' => [
                        'type' => 'images',
                        'id' => (string) $image->getRouteKey(),
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('image')
            ->post('/api/v1/posts');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/posts'), $data)
            ->id();

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'title' => $data['attributes']['title'],
        ]);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => Post::class,
            'imageable_id' => $id,
        ]);
    }

    public function testUpdateReplacesRelationshipWithNull()
    {
        $post = factory(Post::class)->create();

        /** @var Image $image */
        $image = factory(Image::class)->make();
        $image->imageable()->associate($post)->save();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'relationships' => [
                'image' => [
                    'data' => null,
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('image')
            ->patch(url('/api/v1/posts', $post));

        $response
            ->assertFetchedOne($data);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => null,
            'imageable_id' => null,
        ]);
    }

    public function testUpdateReplacesNullRelationshipWithResource()
    {
        $post = factory(Post::class)->create();

        /** @var Image $image */
        $image = factory(Image::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'relationships' => [
                'image' => [
                    'data' => [
                        'type' => 'images',
                        'id' => (string) $image->getRouteKey(),
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('image')
            ->patch(url('/api/v1/posts', $post));

        $response
            ->assertFetchedOne($data);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => Post::class,
            'imageable_id' => $post->getKey(),
        ]);
    }

    public function testUpdateChangesRelatedResource()
    {
        $post = factory(Post::class)->create();

        /** @var Image $existing */
        $existing = factory(Image::class)->make();
        $existing->imageable()->associate($post)->save();

        /** @var Image $expected */
        $expected = factory(Image::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'relationships' => [
                'image' => [
                    'data' => [
                        'type' => 'images',
                        'id' => (string) $expected->getRouteKey(),
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('image')
            ->patch(url('/api/v1/posts', $post));

        $response
            ->assertFetchedOne($data);

        $this->assertDatabaseHas('images', [
            $expected->getKeyName() => $expected->getKey(),
            'imageable_type' => Post::class,
            'imageable_id' => $post->getKey(),
        ]);

        $this->assertDatabaseHas('images', [
            $existing->getKeyName() => $existing->getKey(),
            'imageable_type' => null,
            'imageable_id' => null,
        ]);
    }

    public function testReadRelated()
    {
        $post = factory(Post::class)->create();

        /** @var Image $image */
        $image = factory(Image::class)->make();
        $image->imageable()->associate($post)->save();

        $expected = [
            'type' => 'images',
            'id' => (string) $image->getRouteKey(),
            'attributes' => [
                'url' => $image->url,
            ],
        ];

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', [$post, 'image']));

        $response
            ->assertFetchedOne($expected);
    }

    public function testReadRelatedNull()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', [$post, 'image']));

        $response
            ->assertFetchedNull();
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();

        /** @var Image $image */
        $image = factory(Image::class)->make();
        $image->imageable()->associate($post)->save();

        $response = $this
            ->jsonApi('images')
            ->get(url('/api/v1/posts', [$post, 'relationships', 'image']));

        $response
            ->assertFetchedToOne($image);
    }

    public function testReadEmptyRelationship()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi('images')
            ->get(url('/api/v1/posts', [$post, 'relationships', 'image']));

        $response
            ->assertFetchedNull();
    }

    public function testReplaceNullRelationshipWithRelatedResource()
    {
        $post = factory(Post::class)->create();

        /** @var Image $image */
        $image = factory(Image::class)->create();

        $data = ['type' => 'images', 'id' => (string) $image->getRouteKey()];

        $response = $this
            ->jsonApi('images')
            ->withData($data)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'image']));

        $response
            ->assertStatus(204);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => Post::class,
            'imageable_id' => $post->getKey(),
        ]);
    }

    public function testReplaceRelationshipWithNull()
    {
        $post = factory(Post::class)->create();

        /** @var Image $image */
        $image = factory(Image::class)->create();
        $image->imageable()->associate($post)->save();

        $response = $this
            ->jsonApi('images')
            ->withData(null)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'image']));

        $response
            ->assertStatus(204);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => null,
            'imageable_id' => null,
        ]);
    }

    public function testReplaceRelationshipWithDifferentResource()
    {
        $post = factory(Post::class)->create();

        /** @var Image $existing */
        $existing = factory(Image::class)->make();
        $existing->imageable()->associate($post)->save();

        /** @var Image $image */
        $image = factory(Image::class)->create();

        $data = ['type' => 'images', 'id' => (string) $image->getRouteKey()];

        $response = $this
            ->jsonApi('images')
            ->withData($data)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'image']));

        $response
            ->assertStatus(204);

        $this->assertDatabaseHas('images', [
            $image->getKeyName() => $image->getKey(),
            'imageable_type' => Post::class,
            'imageable_id' => $post->getKey(),
        ]);

        $this->assertDatabaseHas('images', [
            $existing->getKeyName() => $existing->getKey(),
            'imageable_type' => null,
            'imageable_id' => null,
        ]);
    }
}
