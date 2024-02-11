<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

    public function testIndexUnauthenticated()
    {
        $response = $this
            ->jsonApi()
            ->get('/api/v1/tags');

        $response->assertStatus(401)->assertJson([
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
        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->get('/api/v1/tags');

        $response->assertStatus(200);
    }

    public function testCreateUnauthenticated()
    {
        $data = [
            'type' => 'tags',
            'attributes' => [
                'name' => 'News',
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/tags');

        $response->assertStatus(401)->assertJson([
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
        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/tags');

        $response->assertStatus(403)->assertJson([
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
        $response = $this
            ->actingAsUser('author')
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/tags');

        $response->assertStatus(201);
    }

    public function testReadUnauthenticated()
    {
        $tag = factory(Tag::class)->create();

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/tags', $tag));

        $response->assertStatus(401)->assertJson([
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
            'id' => $tag->getRouteKey(),
            'attributes' => [
                'createdAt' => $tag->created_at,
                'updatedAt' => $tag->updated_at,
                'name' => $tag->name,
            ],
            'links' => [
                'self' => "http://localhost/api/v1/tags/{$tag->getRouteKey()}",
            ],
        ];

        $response = $this
            ->actingAsUser('admin')
            ->jsonApi()
            ->get(url('/api/v1/tags', $tag));

        $response
            ->assertStatus(200)
            ->assertExactJson(['data' => $expected]);
    }

    public function testUpdateUnauthenticated()
    {
        $tag = factory(Tag::class)->create();
        $data = [
            'type' => 'tags',
            'id' => $tag->getRouteKey(),
            'attributes' => [
                'name' => 'News',
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/tags', $tag));

        $response->assertStatus(401)->assertJson([
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
            'id' => $tag->getRouteKey(),
            'attributes' => [
                'name' => 'News',
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/tags', $tag));

        $response->assertStatus(403)->assertJson([
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
            'id' => $tag->getRouteKey(),
            'attributes' => [
                'name' => 'News',
            ],
        ];

        $response = $this
            ->actingAsUser('admin')
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/tags', $tag));

        $response->assertStatus(200);
    }


    public function testDeleteUnauthenticated()
    {
        $tag = factory(Tag::class)->create();

        $response = $this
            ->jsonApi()
            ->delete(url('/api/v1/tags', $tag));

        $response->assertStatus(401)->assertJson([
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

        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->delete(url('/api/v1/tags', $tag));

        $response->assertStatus(403)->assertJson([
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

        $response = $this
            ->actingAsUser('admin')
            ->jsonApi()
            ->delete(url('/api/v1/tags', $tag));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tags', ['id' => $tag->getKey()]);
    }

    public function testRelationships()
    {
        $this->markTestIncomplete('@todo');
    }

}
