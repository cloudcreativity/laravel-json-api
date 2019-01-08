<?php

namespace DummyApp\Tests\Feature\Avatars;

use CloudCreativity\LaravelJsonApi\Testing\TestResponse;
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
     */
    public function test(): void
    {
        $user = factory(User::class)->create();
        $file = UploadedFile::fake()->create('avatar.jpg');

        $expected = [
            'type' => 'avatars',
            'attributes' => ['media-type' => 'image/jpeg'],
        ];

        /** @var TestResponse $response */
        $response = $this->actingAs($user, 'api')->post(
            '/api/v1/avatars?include=user',
            ['avatar' => $file],
            ['Content-Type' => 'multipart/form-data', 'Content-Length' => '1']
        );

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
}
