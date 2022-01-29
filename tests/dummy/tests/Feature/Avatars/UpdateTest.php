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

namespace DummyApp\Tests\Feature\Avatars;

use DummyApp\Avatar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateTest extends TestCase
{

    /**
     * @var Avatar
     */
    private $avatar;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->avatar = factory(Avatar::class)->create();
    }

    /**
     * Test that a user can upload an avatar to the API using a standard
     * HTML form post. This means our API must allow a non-JSON API content media type
     * when updating the resource.
     *
     * @param string $contentType
     * @dataProvider multipartProvider
     */
    public function test(string $contentType): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $expected = [
            'type' => 'avatars',
            'id' => (string) $this->avatar->getRouteKey(),
            'attributes' => ['mediaType' => 'image/jpeg'],
        ];

        $this->actingAs($this->avatar->user, 'api');

        $response = $this
            ->jsonApi()
            ->includePaths('user')
            ->content(['avatar' => $file], $contentType)
            ->patch(url('/api/v1/avatars', $this->avatar));

        $response
            ->assertFetchedOne($expected)
            ->assertIsIncluded('users', $this->avatar->user)
            ->id();

        $this->assertDatabaseHas('avatars', [
            'id' => $this->avatar->getKey(),
            'media_type' => 'image/jpeg',
            'user_id' => $this->avatar->user->getKey(),
        ]);

        $path = Avatar::whereKey($this->avatar->getKey())->value('path');

        Storage::disk('local')->assertExists($path);
    }
}
