<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Auth;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\User;
use Illuminate\Support\Facades\Auth;

class LoginTest extends TestCase
{

    public function test()
    {
        $user = factory(User::class)->create([
            'password' => bcrypt('secret'),
        ]);

        $expected = [
            'data' => [
                'type' => 'users',
                'id' => $user->getRouteKey(),
                'attributes' => [
                    'name' => $user->name,
                ],
            ],
        ];

        $this->doLogin(['email' => $user->email, 'password' => 'secret'])
            ->assertSuccessful()
            ->assertJson($expected);

        $this->assertEquals($user->getKey(), Auth::id());
    }

    public function testInvalid()
    {
        $user = factory(User::class)->create([
            'password' => bcrypt('secret'),
        ]);

        $this->doLogin(['email' => $user->email, 'password' => 'foo'])
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    [
                        'title' => 'Unprocessable Entity',
                        'status' => '422',
                        'meta' => ['key' => 'email'],
                    ],
                ],
            ]);
    }

    /**
     * @param $credentials
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    private function doLogin($credentials)
    {
        return $this->postJson(
            route('login'),
            $credentials,
            ['Accept' => 'application/vnd.api+json']
        );
    }
}
