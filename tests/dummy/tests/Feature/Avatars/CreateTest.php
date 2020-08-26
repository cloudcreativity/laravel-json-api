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

use DummyApp\Avatar;
use DummyApp\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CreateTest extends TestCase
{

    /**
     * Test that a user can upload an avatar to the API using a standard
     * HTML form post. This means our API must allow a non-JSON API content media type
     * when creating the resource.
     *
     * @param string $contentType
     * @dataProvider multipartProvider
     */
    public function test(string $contentType): void
    {
        $user = factory(User::class)->create();
        $file = UploadedFile::fake()->create('avatar.jpg');

        $expected = [
            'type' => 'avatars',
            'attributes' => ['mediaType' => 'image/jpeg'],
        ];

        $this->actingAs($user, 'api');

        $response = $this
            ->jsonApi()
            ->includePaths('user')
            ->contentType($contentType)
            ->withPayload(['avatar' => $file])
            ->post('/api/v1/avatars');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/avatars'), $expected)
            ->assertIsIncluded('users', $user)
            ->id();

        $this->assertDatabaseHas('avatars', [
            'id' => $id,
            'media_type' => 'image/jpeg',
            'user_id' => $user->getKey(),
        ]);

        $path = Avatar::whereKey($id)->value('path');

        Storage::disk('local')->assertExists($path);
    }

    public function testFileIsRequired(): void
    {
        $user = factory(User::class)->create();

        $this->actingAs($user, 'api');

        $response = $this
            ->jsonApi()
            ->contentType('multipart/form-data')
            ->content(['avatar' => null])
            ->post('/api/v1/avatars');

        $response->assertExactErrorStatus([
            'title' => 'Unprocessable Entity',
            'status' => '422',
            'detail' => 'The avatar field is required.',
        ]);
    }
}
