<?php

namespace DummyApp\Tests\Feature\Avatars;

use DummyApp\Avatar;

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
        $this->markTestSkipped('@todo');

        $avatar = factory(Avatar::class)->create();

        $this->withAcceptMediaType('image/*')
            ->doRead($avatar)
            ->assertSuccessful()
            ->assertHeader('Content-Type', $avatar->media_type);
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
