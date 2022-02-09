<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Phone;
use DummyApp\User;

/**
 * Class HasOneTest
 *
 * Tests a JSON API has-one relationship, which is used for an
 * Eloquent has-one relationship. In our dummy application,
 * this is the `phone` relationship on the `users` resource.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class HasOneTest extends TestCase
{

    /**
     * We can create a user resource providing `null` as the phone relationship.
     */
    public function testCreateWithNull()
    {
        /** @var User $user */
        $user = factory(User::class)->make();

        $data = [
            'type' => 'users',
            'attributes' => [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'secret',
                // @see https://github.com/cloudcreativity/laravel-json-api/issues/262
                'passwordConfirmation' => 'secret',
            ],
            'relationships' => [
                'phone' => [
                    'data' => null,
                ],
            ],
        ];

        $expected = $data;
        unset($expected['attributes']['password'], $expected['attributes']['passwordConfirmation']);

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('phone')
            ->post('/api/v1/users');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/users'), $expected)
            ->id();

        $this->assertNotNull($refreshed = User::find($id));
        $this->assertNull($refreshed->phone);
    }

    /**
     * @return array
     */
    public function confirmationProvider(): array
    {
        return [
            ['passwordConfirmation', 'foo'],
            ['passwordConfirmation', null],
            ['password', 'foo'],
        ];
    }

    /**
     * @param string $field
     * @param $value
     * @dataProvider confirmationProvider
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/262
     */
    public function testCreatePasswordNotConfirmed(string $field, $value): void
    {
        /** @var User $user */
        $user = factory(User::class)->make();

        $data = ResourceObject::create([
            'type' => 'users',
            'attributes' => [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'secret',
                'passwordConfirmation' => 'secret',
            ],
            'relationships' => [
                'phone' => [
                    'data' => null,
                ],
            ],
        ])->replace($field, $value);

        $expected = [
            'status' => '422',
            'source' => [
                'pointer' => '/data/attributes/passwordConfirmation',
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('phone')
            ->post('/api/v1/users');

        $response->assertErrorStatus($expected);
    }

    /**
     * We can create a user resource providing a related phone.
     */
    public function testCreateWithRelated()
    {
        /** @var Phone $phone */
        $phone = factory(Phone::class)->create();
        /** @var User $user */
        $user = factory(User::class)->make();

        $data = [
            'type' => 'users',
            'attributes' => [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'secret',
                'passwordConfirmation' => 'secret',
            ],
            'relationships' => [
                'phone' => [
                    'data' => [
                        'type' => 'phones',
                        'id' => (string) $phone->getKey(),
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['attributes']['password'], $expected['attributes']['passwordConfirmation']);

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('phone')
            ->post('/api/v1/users');

        $id = $response
            ->assertCreatedWithServerId(url('/api/v1/users'), $expected)
            ->id();

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $id,
        ]);
    }

    /**
     * A user with an existing phone can have the phone replaced with null.
     */
    public function testUpdateReplacesRelationshipWithNull()
    {
        /** @var Phone $phone */
        $phone = factory(Phone::class)->states('user')->create();

        $data = [
            'type' => 'users',
            'id' => (string) $phone->user_id,
            'relationships' => [
                'phone' => [
                    'data' => null,
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('phone')
            ->patch(url('/api/v1/users', $phone->user_id));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => null,
        ]);
    }

    /**
     * A user linked to no phone can update their phone to a related resource.
     */
    public function testUpdateReplacesNullRelationshipWithResource()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Phone $phone */
        $phone = factory(Phone::class)->create();

        $data = [
            'type' => 'users',
            'id' => (string) $user->getKey(),
            'relationships' => [
                'phone' => [
                    'data' => [
                        'type' => 'phones',
                        'id' => (string) $phone->getKey(),
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('phone')
            ->patch(url('/api/v1/users', $user));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    /**
     * A user linked to an existing phone can change to another phone.
     */
    public function testUpdateChangesRelatedResource()
    {
        /** @var Phone $existing */
        $existing = factory(Phone::class)->states('user')->create();
        /** @var Phone $other */
        $other = factory(Phone::class)->create();

        $data = [
            'type' => 'users',
            'id' => (string) $existing->user_id,
            'relationships' => [
                'phone' => [
                    'data' => [
                        'type' => 'phones',
                        'id' => (string) $other->getKey(),
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->includePaths('phone')
            ->patch(url('/api/v1/users', $existing->user_id));

        $response->assertFetchedOne($data);

        $this->assertDatabaseHas('phones', [
            'id' => $existing->getKey(),
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('phones', [
            'id' => $other->getKey(),
            'user_id' => $existing->user_id,
        ]);
    }

    /**
     * Test that we can read the related phone.
     */
    public function testReadRelated()
    {
        /** @var Phone $phone */
        $phone = factory(Phone::class)->states('user')->create();
        /** @var User $user */
        $user = $phone->user;

        $data = [
            'type' => 'phones',
            'id' => (string) $phone->getKey(),
            'attributes' => [
                'number' => $phone->number,
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->getKey(),
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->includePaths('user')
            ->get(url('/api/v1/users', [$user, 'phone']));

        $response->assertFetchedOne($data);
    }

    /**
     * Test that we can read the resource identifier for the related phone.
     */
    public function testReadRelationship()
    {
        /** @var Phone $phone */
        $phone = factory(Phone::class)->states('user')->create();

        $response = $this
            ->jsonApi('phones')
            ->includePaths('user')
            ->get(url('/api/v1/users', [$phone->user, 'relationships', 'phone']));

        $response
            ->assertFetchedToOne($phone);
    }

    /**
     * Test that we can replace a null relationship with a related resource.
     */
    public function testReplaceNullRelationshipWithRelatedResource()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Phone $phone */
        $phone = factory(Phone::class)->create();

        $data = ['type' => 'phones', 'id' => (string) $phone->getRouteKey()];

        $response = $this
            ->jsonApi('phones')
            ->withData($data)
            ->patch(url('/api/v1/users', [$user, 'relationships', 'phone']));

        $response
            ->assertStatus(204);

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    /**
     * Test that we can clear the related phone relationship.
     */
    public function testReplaceRelationshipWithNull()
    {
        /** @var Phone $phone */
        $phone = factory(Phone::class)->states('user')->create();

        /** @var Phone $other */
        $other = factory(Phone::class)->states('user')->create();

        $response = $this
            ->jsonApi('phones')
            ->withData(null)
            ->patch(url('/api/v1/users', [$phone->user, 'relationships', 'phone']));

        $response
            ->assertStatus(204);

        $this->assertDatabaseHas('phones', [
            'id' => $phone->getKey(),
            'user_id' => null,
        ]);

        /** The other phone must be unaffected. */
        $this->assertDatabaseHas('phones', [
            'id' => $other->getKey(),
            'user_id' => $other->user_id,
        ]);
    }

    /**
     * Test that we can replace a related resource with a different one.
     */
    public function testReplaceRelationshipWithDifferentResource()
    {
        /** @var Phone $existing */
        $existing = factory(Phone::class)->states('user')->create();
        /** @var Phone $other */
        $other = factory(Phone::class)->create();

        $data = ['type' => 'phones', 'id' => (string) $other->getRouteKey()];

        $response = $this
            ->jsonApi('phones')
            ->withData($data)
            ->patch(url('/api/v1/users', [$existing->user, 'relationships', 'phone']));

        $response
            ->assertStatus(204);

        $this->assertDatabaseHas('phones', [
            'id' => $existing->getKey(),
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('phones', [
            'id' => $other->getKey(),
            'user_id' => $existing->user_id,
        ]);
    }
}
