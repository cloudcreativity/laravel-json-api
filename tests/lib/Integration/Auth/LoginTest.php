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
