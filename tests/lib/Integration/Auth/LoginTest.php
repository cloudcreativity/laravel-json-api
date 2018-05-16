<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Auth;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\User;

class LoginTest extends TestCase
{

    public function testLogin()
    {
        $user = factory(User::class)->create([
            'password' => bcrypt('secret'),
        ]);

        $credentials = ['email' => $user->email, 'password' => 'secret'];

        $expected = [
            'data' => [
                'type' => 'users',
                'id' => $user->getKey(),
                'attributes' => [
                    'name' => $user->name,
                ],
            ],
        ];

        $this->postJson('/login', $credentials, ['Accept' => 'application/vnd.api+json'])
            ->assertSuccessful()
            ->assertJson($expected);
    }
}
