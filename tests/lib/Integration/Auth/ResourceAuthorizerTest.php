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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Auth;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Tag;

class ResourceAuthorizerTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'tags';

    public function testIndexUnauthenticated()
    {
        $this->doSearch()->assertStatus(401)->assertJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);
    }

    public function testIndexAllowed()
    {
        $this->actingAsUser()
            ->doSearch()
            ->assertStatus(200);
    }

    public function testCreateUnauthenticated()
    {
        $data = [
            'type' => 'tags',
            'attributes' => [
                'name' => 'News',
            ],
        ];

        $this->doCreate($data)->assertStatus(401)->assertJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);

        return $data;
    }

    /**
     * @param array $data
     * @depends testCreateUnauthenticated
     */
    public function testCreateUnauthorized(array $data)
    {
        $this->actingAsUser()->doCreate($data)->assertStatus(403)->assertJson([
            'errors' => [
                [
                    'title' => 'Unauthorized',
                    'status' => '403',
                ],
            ],
        ]);
    }

    /**
     * @param array $data
     * @depends testCreateUnauthenticated
     */
    public function testCreateAllowed(array $data)
    {
        $this->actingAsUser('author')
            ->doCreate($data)
            ->assertStatus(201);
    }

    public function testReadUnauthenticated()
    {
        $tag = factory(Tag::class)->create();

        $this->doRead($tag->uuid)->assertStatus(401)->assertJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);
    }

    public function testReadAllowed()
    {
        $tag = factory(Tag::class)->create();

        $expected = [
            'type' => 'tags',
            'id' => $tag->uuid,
            'attributes' => [
                'created-at' => $tag->created_at->toAtomString(),
                'updated-at' => $tag->updated_at->toAtomString(),
                'name' => $tag->name,
            ],
            'links' => [
                'self' => "http://localhost/api/v1/tags/{$tag->uuid}",
            ],
        ];

        $this->actingAsUser('admin')
            ->doRead($tag->uuid)
            ->assertStatus(200)
            ->assertExactJson(['data' => $expected]);
    }

    public function testUpdateUnauthenticated()
    {
        $tag = factory(Tag::class)->create();
        $data = [
            'type' => 'tags',
            'id' => $tag->uuid,
            'attributes' => [
                'name' => 'News',
            ],
        ];

        $this->doUpdate($data)->assertStatus(401)->assertJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);
    }

    public function testUpdateUnauthorized()
    {
        $tag = factory(Tag::class)->create();
        $data = [
            'type' => 'tags',
            'id' => $tag->uuid,
            'attributes' => [
                'name' => 'News',
            ],
        ];

        $this->actingAsUser()->doUpdate($data)->assertStatus(403)->assertJson([
            'errors' => [
                [
                    'title' => 'Unauthorized',
                    'status' => '403',
                ],
            ],
        ]);
    }

    public function testUpdateAllowed()
    {
        $tag = factory(Tag::class)->create();
        $data = [
            'type' => 'tags',
            'id' => $tag->uuid,
            'attributes' => [
                'name' => 'News',
            ],
        ];

        $this->actingAsUser('admin')
            ->doUpdate($data)
            ->assertStatus(200);
    }


    public function testDeleteUnauthenticated()
    {
        $tag = factory(Tag::class)->create();

        $this->doDelete($tag->uuid)->assertStatus(401)->assertJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);

        $this->assertDatabaseHas('tags', ['id' => $tag->getKey()]);
    }

    public function testDeleteUnauthorized()
    {
        $tag = factory(Tag::class)->create();

        $this->actingAsUser()->doDelete($tag->uuid)->assertStatus(403)->assertJson([
            'errors' => [
                [
                    'title' => 'Unauthorized',
                    'status' => '403',
                ],
            ],
        ]);

        $this->assertDatabaseHas('tags', ['id' => $tag->getKey()]);
    }

    public function testDeleteAllowed()
    {
        $tag = factory(Tag::class)->create();

        $this->actingAsUser('admin')
            ->doDelete($tag->uuid)
            ->assertStatus(204);

        $this->assertDatabaseMissing('tags', ['id' => $tag->getKey()]);
    }

}
