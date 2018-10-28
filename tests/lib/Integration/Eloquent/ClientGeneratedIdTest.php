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

use DummyApp\Video;

class ClientGeneratedIdTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'videos';

    public function testCreate()
    {
        $video = factory(Video::class)->make();

        $data = [
            'type' => 'videos',
            'id' => $video->getKey(),
            'attributes' => [
                'url' => $video->url,
                'title' => $video->title,
                'description' => $video->description,
            ],
        ];

        $expected = $data;
        $expected['relationships'] = [
            'uploaded-by' => [
                'data' => [
                    'type' => 'users',
                    'id' => (string) $video->user_id,
                ],
            ],
        ];

        $this->actingAs($video->user);

        $this->doCreate($data, ['include' => 'uploaded-by'])->assertCreatedWithClientId(
            'http://localhost/api/v1/videos',
            $expected
        );

        $this->assertDatabaseHas('videos', ['uuid' => $video->getKey()]);
    }

    public function testCreateWithMissingId()
    {
        $video = factory(Video::class)->make();

        $data = [
            'type' => 'videos',
            'attributes' => [
                'url' => $video->url,
                'title' => $video->title,
                'description' => $video->description,
            ],
        ];

        $error = [
            'title' => 'Unprocessable Entity',
            'detail' => 'The id field is required.',
            'status' => '422',
            'source' => ['pointer' => '/data/id'],
        ];

        $this->actingAs($video->user);

        $this->doCreate($data)
            ->assertStatus((int) $error['status'])
            ->assertJson(['errors' => [$error]]);
    }

    public function testCreateWithInvalidId()
    {
        $video = factory(Video::class)->make();

        $data = [
            'type' => 'videos',
            'id' => 'foo',
            'attributes' => [
                'url' => $video->url,
                'title' => $video->title,
                'description' => $video->description,
            ],
        ];

        $error = [
            'title' => 'Unprocessable Entity',
            'detail' => 'The id format is invalid.',
            'status' => '422',
            'source' => ['pointer' => '/data/id'],
        ];

        $this->actingAs($video->user);

        $this->doCreate($data)
            ->assertStatus((int) $error['status'])
            ->assertJson(['errors' => [$error]]);
    }

    public function testCreateWithConflict()
    {
        $video = factory(Video::class)->create();

        $data = [
            'type' => 'videos',
            'id' => $video->getKey(),
            'attributes' => [
                'url' => $video->url,
                'title' => $video->title,
                'description' => $video->description,
            ],
        ];

        $error = [
            'title' => 'Conflict',
            'detail' => "Resource {$video->getKey()} already exists.",
            'status' => '409',
            'source' => ['pointer' => '/data'],
        ];

        $this->actingAs($video->user);

        $this->doCreate($data)
            ->assertStatus((int) $error['status'])
            ->assertJson(['errors' => [$error]]);
    }

    public function testUpdated()
    {
        $video = factory(Video::class)->create();

        $data = [
            'type' => 'videos',
            'id' => $video->getKey(),
            'attributes' => [
                'title' => 'A Video',
            ],
        ];

        $expected = $data;

        $this->actingAs($video->user);

        $this->doUpdate($data)->assertUpdated($expected);

        $this->assertDatabaseHas('videos', [
            'uuid' => $video->getKey(),
            'title' => 'A Video',
        ]);
    }

}
