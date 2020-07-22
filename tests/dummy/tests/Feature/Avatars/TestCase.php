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

namespace DummyApp\Tests\Feature\Avatars;

use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase as BaseTestCase;
use DummyApp\Avatar;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'avatars';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /**
     * @return array
     */
    public function fieldProvider(): array
    {
        return [
            'created-at' => ['created-at'],
            'media-type' => ['media-type'],
            'updated-at' => ['updated-at'],
            'user' => ['user'],
        ];
    }

    /**
     * @return array
     */
    public function multipartProvider(): array
    {
        return [
            'form-data' => ['multipart/form-data'],
            'form-data w/boundary' => ['multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW'],
        ];
    }

    /**
     * Get the expected JSON API resource for the avatar model.
     *
     * @param Avatar $avatar
     * @return ResourceObject
     */
    protected function serialize(Avatar $avatar): ResourceObject
    {
        $self = url("/api/v1/avatars", $avatar);

        return ResourceObject::create([
            'type' => 'avatars',
            'id' => (string) $avatar->getRouteKey(),
            'attributes' => [
                'createdAt' => $avatar->created_at->toJSON(),
                'mediaType' => $avatar->media_type,
                'updatedAt' => $avatar->updated_at->toJSON(),
            ],
            'relationships' => [
                'user' => [
                    'links' => [
                        'self' => "{$self}/relationships/user",
                        'related' => "{$self}/user",
                    ],
                ],
            ],
            'links' => [
                'self' => $self,
            ],
        ]);
    }
}
