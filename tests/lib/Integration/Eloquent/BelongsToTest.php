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

use DummyApp\Post;
use DummyApp\User;

/**
 * Class BelongsToTest
 *
 * Tests a JSON API has-one relationship that relates to an Eloquent belongs-to
 * relationship.
 *
 * In our dummy app, this is the author relationship on the post model.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class BelongsToTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    public function testCreateWithNull()
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
                'author' => [
                    'data' => null,
                ],
            ],
        ];

        $id = $this
            ->doCreate($data, ['include' => 'author'])
            ->assertCreatedWithId($data);

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'author_id' => null,
        ]);
    }

    public function testCreateWithRelated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->make();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $post->author_id,
                    ],
                ],
            ],
        ];

        $id = $this
            ->doCreate($data, ['include' => 'author'])
            ->assertCreatedWithId($data);

        $this->assertDatabaseHas('posts', [
            'id' => $id,
            'author_id' => $post->author_id,
        ]);
    }

    public function testUpdateReplacesRelationshipWithNull()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => null,
                ],
            ],
        ];

        $this->doUpdate($data, ['include' => 'author'])->assertUpdated($data);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => null,
        ]);
    }

    public function testUpdateReplacesNullRelationshipWithResource()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'author_id' => null,
        ]);

        /** @var User $user */
        $user = factory(User::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->getRouteKey(),
                    ],
                ],
            ],
        ];

        $this->doUpdate($data, ['include' => 'author'])->assertUpdated($data);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => $user->getKey(),
        ]);
    }

    public function testUpdateChangesRelatedResource()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        $this->assertNotNull($post->author_id);

        /** @var User $user */
        $user = factory(User::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->getRouteKey(),
                    ],
                ],
            ],
        ];

        $this->doUpdate($data, ['include' => 'author'])->assertUpdated($data);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => $user->getKey(),
        ]);
    }

    public function testReadRelated()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        /** @var User $user */
        $user = $post->author;

        $expected = [
            'type' => 'users',
            'id' => (string) $user->getKey(),
            'attributes' => [
                'name' => $user->name,
            ],
        ];

        $this->doReadRelated($post, 'author')
            ->assertReadHasOne($expected);
    }

    public function testReadRelatedNull()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create([
            'author_id' => null,
        ]);

        $this->doReadRelated($post, 'author')
            ->assertReadHasOne(null);
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelationship($post, 'author')
            ->assertReadHasOneIdentifier('users', (string) $post->author_id);
    }

    public function testReadEmptyRelationship()
    {
        $post = factory(Post::class)->create([
            'author_id' => null,
        ]);

        $this->doReadRelationship($post, 'author')
            ->assertReadHasOneIdentifier(null);
    }

    public function testReplaceNullRelationshipWithRelatedResource()
    {
        $post = factory(Post::class)->create([
            'author_id' => null,
        ]);

        $user = factory(User::class)->create();

        $data = ['type' => 'users', 'id' => (string) $user->getKey()];

        $this->doReplaceRelationship($post, 'author', $data)
            ->assertStatus(204);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => $user->getKey(),
        ]);
    }

    public function testReplaceRelationshipWithNull()
    {
        $post = factory(Post::class)->create();
        $this->assertNotNull($post->author_id);

        $this->doReplaceRelationship($post, 'author', null)
            ->assertStatus(204);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => null,
        ]);
    }

    public function testReplaceRelationshipWithDifferentResource()
    {
        $post = factory(Post::class)->create();
        $this->assertNotNull($post->author_id);

        $user = factory(User::class)->create();

        $data = ['type' => 'users', 'id' => (string) $user->getKey()];

        $this->doReplaceRelationship($post, 'author', $data)
            ->assertStatus(204);

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => $user->getKey(),
        ]);
    }
}
