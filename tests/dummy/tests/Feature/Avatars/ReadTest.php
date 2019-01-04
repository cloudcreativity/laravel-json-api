<?php

namespace DummyApp\Tests\Feature\Avatars;

use DummyApp\Avatar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ReadTest extends TestCase
{

    /**
     * Test that reading an avatar returns the exact resource we are expecting.
     */
    public function test(): void
    {
        $avatar = factory(Avatar::class)->create();
        $expected = $this->serialize($avatar)->toArray();

        $this->doRead($avatar)
            ->assertFetchedOneExact($expected);
    }

    /**
     * Test that reading the avatar with an image media tye results in it being downloaded.
     */
    public function testDownload(): void
    {
        Storage::fake('local');

        $path = UploadedFile::fake()->create('avatar.jpg')->store('avatars');
        $avatar = factory(Avatar::class)->create(compact('path'));

        $this->withAcceptMediaType('image/*')
            ->doRead($avatar)
            ->assertSuccessful()
            ->assertHeader('Content-Type', $avatar->media_type);
    }

    /**
     * If the avatar model exists, but the file doesn't, we need to get an error back. As
     * we have not requested JSON API, this should be the standard Laravel error i.e.
     * `text/html`.
     */
    public function testDownloadFileDoesNotExist(): void
    {
        $path = 'avatars/does-not-exist.jpg';
        $avatar = factory(Avatar::class)->create(compact('path'));

        $this->withAcceptMediaType('image/*')
            ->doRead($avatar)
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Test that we can include the user in the response.
     */
    public function testIncludeUser(): void
    {
        $avatar = factory(Avatar::class)->create();
        $userId = ['type' => 'users', 'id' => (string) $avatar->user_id];

        $expected = $this
            ->serialize($avatar)
            ->replace('user', $userId)
            ->toArray();

        $this->doRead($avatar, ['include' => 'user'])
            ->assertFetchedOneExact($expected)
            ->assertIncluded($userId);
    }

    /**
     * Test that include fields are validated.
     */
    public function testInvalidInclude(): void
    {
        $avatar = factory(Avatar::class)->create();

        $expected = [
            'status' => '400',
            'detail' => 'Include path foo is not allowed.',
            'source' => ['parameter' => 'include'],
        ];

        $this->doRead($avatar, ['include' => 'foo'])
            ->assertErrorStatus($expected);
    }

    /**
     * @param string $field
     * @dataProvider fieldProvider
     */
    public function testSparseFieldset(string $field): void
    {
        $avatar = factory(Avatar::class)->create();
        $expected = $this->serialize($avatar)->only($field)->toArray();
        $fields = ['avatars' => $field];

        $this->doRead($avatar, compact('fields'))->assertFetchedOneExact($expected);
    }
}
