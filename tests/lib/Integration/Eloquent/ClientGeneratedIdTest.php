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

    public function testCreateWithClientId()
    {
        $video = factory(Video::class)->make();

        $data = [
            'type' => 'videos',
            'id' => $video->getKey(),
            'attributes' => [
                'title' => $video->title,
                'description' => $video->description,
            ],
        ];

        $expected = $data;
        $expected['relationships'] = [
            'uploaded-by' => [
                'data' => [
                    'type' => 'users',
                    'id' => $video->user_id,
                ],
            ],
        ];

        $this->actingAs($video->user);

        $this->doCreate($data)
            ->assertCreated($expected);

        $this->assertModelCreated($video, $video->getKey());
    }

    public function testCreateWithInvalidClientId()
    {
        $this->markTestIncomplete('@todo when it is possible to validate client ids.');
    }

}
