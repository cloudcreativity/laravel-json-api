<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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
use DummyApp\Video;

class GuardedAttributesTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'videos';

    /**
     * An adapter must be allowed to 'guard' some fields - i.e. prevent them
     * from being filled to the model. The video adapter in our dummy app is
     * set to guard the `url` field if the model already exists.
     */
    public function test()
    {
        /** @var Video $video */
        $video = factory(Video::class)->create();

        $data = [
            'type' => 'videos',
            'id' => $video->getKey(),
            'attributes' => [
                'url' => 'http://www.example.com',
                'title' => 'My Video',
                'description' => 'This is my video.',
            ],
        ];

        $expected = $data;
        $expected['attributes']['url'] = $video->url;

        $this->doUpdate($data)->assertFetchedOne($expected);
    }
}
