<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Country;
use DummyApp\Phone;
use DummyApp\Role;
use DummyApp\User;

/**
 * Class BelongsToMany
 *
 * Test a JSON API has-many relationship that relates to an Eloquent
 * belongs-to-many relationship.
 *
 * In our dummy app, this is the role/user relationship.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class BelongsToManyTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'users';

    public function testCreateWithEmpty(): void
    {
        /** @var User $user */
        $user = factory(User::class)->make();

        $data = [
            'type' => 'users',
            'attributes' => [
                'email' => $user->email,
                'name' => $user->name,
                'password' => 'secret',
                'passwordConfirmation' => 'secret',
            ],
            'relationships' => [
                'roles' => [
                    'data' => [],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['attributes']['password'], $expected['attributes']['passwordConfirmation']);

        $response = $this
            ->jsonApi()
            ->includePaths('roles')
            ->withData($data)
            ->post('/api/v1/users');

        $response->assertCreatedWithServerId(url('/api/v1/users'), $expected);
        $this->assertDatabaseMissing('role_user', ['user_id' => $response->id()]);
    }

    public function testCreateWithRelated(): void
    {
        /** @var User $user */
        $user = factory(User::class)->make();
        $role = factory(Role::class)->create();

        $data = [
            'type' => 'users',
            'attributes' => [
                'email' => $user->email,
                'name' => $user->name,
                'password' => 'secret',
                'passwordConfirmation' => 'secret',
            ],
            'relationships' => [
                'roles' => [
                    'data' => [
                        [
                            'type' => 'roles',
                            'id' => (string) $role->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['attributes']['password'], $expected['attributes']['passwordConfirmation']);

        $response = $this
            ->jsonApi()
            ->includePaths('roles')
            ->withData($data)
            ->post('/api/v1/users');

        $response->assertCreatedWithServerId(url('/api/v1/users'), $expected);

        $this->assertDatabaseCount('role_user', 1);
        $this->assertDatabaseHas('role_user', [
            'user_id' => $response->id(),
            'role_id' => $role->getKey(),
        ]);
    }

    public function testCreateWithManyRelated(): void
    {
        /** @var User $user */
        $user = factory(User::class)->make();
        $roles = factory(Role::class, 2)->create();

        $data = [
            'type' => 'users',
            'attributes' => [
                'email' => $user->email,
                'name' => $user->name,
                'password' => 'secret',
                'passwordConfirmation' => 'secret',
            ],
            'relationships' => [
                'roles' => [
                    'data' => [
                        [
                            'type' => 'roles',
                            'id' => (string) $roles[0]->getRouteKey(),
                        ],
                        [
                            'type' => 'roles',
                            'id' => (string) $roles[1]->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['attributes']['password'], $expected['attributes']['passwordConfirmation']);

        $response = $this
            ->jsonApi()
            ->includePaths('roles')
            ->withData($data)
            ->post('/api/v1/users');

        $response->assertCreatedWithServerId(url('/api/v1/users'), $expected);

        $this->assertDatabaseCount('role_user', count($roles));

        foreach ($roles as $role) {
            $this->assertDatabaseHas('role_user', [
                'user_id' => $response->id(),
                'role_id' => $role->getKey(),
            ]);
        }
    }

    public function testUpdateReplacesRelationshipWithEmptyRelationship(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->roles()->saveMany(factory(Role::class, 2)->create());

        $data = [
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
            'relationships' => [
                'roles' => [
                    'data' => [],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->includePaths('roles')
            ->withData($data)
            ->patch(url('/api/v1/users', $user));

        $response->assertFetchedOne($data);

        $this->assertDatabaseCount('role_user', 0);
    }

    public function testUpdateReplacesEmptyRelationshipWithResource(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();

        $data = [
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
            'relationships' => [
                'roles' => [
                    'data' => [
                        [
                            'type' => 'roles',
                            'id' => (string) $role->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->includePaths('roles')
            ->withData($data)
            ->patch(url('/api/v1/users', $user));

        $response->assertFetchedOne($data);

        $this->assertDatabaseCount('role_user', 1);
        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->getKey(),
            'role_id' => $role->getKey(),
        ]);
    }

    public function testUpdateChangesRelatedResources(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->roles()->saveMany(factory(Role::class, 2)->create());

        $roles = factory(Role::class, 2)->create();

        $data = [
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
            'relationships' => [
                'roles' => [
                    'data' => [
                        [
                            'type' => 'roles',
                            'id' => (string) $roles[0]->getRouteKey(),
                        ],
                        [
                            'type' => 'roles',
                            'id' => (string) $roles[1]->getRouteKey(),
                        ],
                    ],
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->includePaths('roles')
            ->withData($data)
            ->patch(url('/api/v1/users', $user));

        $response->assertFetchedOne($data);

        $this->assertDatabaseCount('role_user', 2);

        foreach ($roles as $role) {
            $this->assertDatabaseHas('role_user', [
                'user_id' => $user->getKey(),
                'role_id' => $role->getKey(),
            ]);
        }
    }

    /**
     * In this test we keep one existing role, and add two new ones.
     */
    public function testUpdateSyncsRelatedResources(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->roles()->saveMany($existing = factory(Role::class, 2)->create());

        $roles = factory(Role::class, 2)->create();

        $expected = $roles->merge([$existing[0]]);

        $data = [
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
            'relationships' => [
                'roles' => [
                    'data' => $expected->map(function (Role $role) {
                        return ['type' => 'roles', 'id' => (string) $role->getRouteKey()];
                    })->sortBy('id')->values()->all(),
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->includePaths('roles')
            ->withData($data)
            ->patch(url('/api/v1/users', $user));

        $response->assertFetchedOne($data);

        $this->assertDatabaseCount('role_user', count($expected));

        foreach ($expected as $role) {
            $this->assertDatabaseHas('role_user', [
                'user_id' => $user->getKey(),
                'role_id' => $role->getKey(),
            ]);
        }
    }

    public function testReadRelated(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->roles()->saveMany($roles = factory(Role::class, 2)->create());

        $response = $this
            ->jsonApi()
            ->expects('roles')
            ->get(url('/api/v1/users', [$user, 'roles']));

        $response->assertFetchedMany($roles);
    }

    public function testReadRelatedEmpty(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $response = $this
            ->jsonApi()
            ->expects('roles')
            ->get(url('/api/v1/users', [$user, 'roles']));

        $response->assertFetchedNone();
    }

    public function testReadRelatedWithFilter(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $a = factory(Role::class)->create(['name' => 'Role AA']);
        $b = factory(Role::class)->create(['name' => 'Role AB']);
        $c = factory(Role::class)->create(['name' => 'Role C']);

        $user->roles()->saveMany([$a, $b, $c]);

        $response = $this
            ->jsonApi()
            ->expects('roles')
            ->filter(['name' => 'Role A'])
            ->get(url('/api/v1/users', [$user, 'roles']));

        $response->assertFetchedMany([$a, $b]);
    }

    public function testReadRelatedWithInvalidFilter(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $response = $this
            ->jsonApi()
            ->expects('roles')
            ->filter(['name' => ''])
            ->get(url('/api/v1/users', [$user, 'roles']));

        $response->assertErrorStatus([
            'status' => '400',
            'detail' => 'The filter.name field must have a value.',
        ]);
    }

    public function testReadRelationship(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->roles()->saveMany($roles = factory(Role::class, 2)->create());

        $response = $this
            ->jsonApi()
            ->expects('roles')
            ->get(url('/api/v1/users', [$user, 'relationships', 'roles']));

        $response->assertFetchedToMany($roles);
    }

    public function testReadEmptyRelationship(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $response = $this
            ->jsonApi()
            ->expects('roles')
            ->get(url('/api/v1/users', [$user, 'relationships', 'roles']));

        $response->assertFetchedNone();
    }

    public function testReplaceEmptyRelationshipWithRelatedResource(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $roles = factory(Role::class, 2)->create();

        $data = $roles->map(function (Role $user) {
            return ['type' => 'roles', 'id' => (string) $user->getRouteKey()];
        })->all();

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/users', [$user, 'relationships', 'roles']));

        $response->assertNoContent();

        $this->assertDatabaseCount('role_user', count($roles));

        foreach ($roles as $role) {
            $this->assertDatabaseHas('role_user', [
                'user_id' => $user->getKey(),
                'role_id' => $role->getKey(),
            ]);
        }
    }

    public function testReplaceEmptyRelationshipWithNone(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->roles()->saveMany(factory(Role::class, 2)->create());

        $response = $this
            ->jsonApi()
            ->withData([])
            ->patch(url('/api/v1/users', [$user, 'relationships', 'roles']));

        $response->assertNoContent();

        $this->assertDatabaseCount('role_user', 0);
    }

    public function testReplaceRelationshipWithDifferentResources(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->roles()->saveMany(factory(Role::class, 2)->create());
        $roles = factory(Role::class, 3)->create();

        $data = $roles->map(function (Role $role) {
            return ['type' => 'roles', 'id' => (string) $role->getRouteKey()];
        });

        /** Add a duplicate - expecting that resource to only be added once. */
        $data->push(['type' => 'roles', 'id' => (string) $roles[1]->getRouteKey()]);

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/users', [$user, 'relationships', 'roles']));

        $response->assertNoContent();

        $this->assertDatabaseCount('role_user', count($roles));

        foreach ($roles as $role) {
            $this->assertDatabaseHas('role_user', [
                'user_id' => $user->getKey(),
                'role_id' => $role->getKey(),
            ]);
        }
    }

    public function testAddToRelationship(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->roles()->saveMany($existing = factory(Role::class, 2)->create());

        $add = factory(Role::class, 2)->create();
        $data = $add->map(function (Role $role) {
            return ['type' => 'roles', 'id' => (string) $role->getRouteKey()];
        });

        /** Add an existing role: this should not be added twice */
        $data->push(['type' => 'roles', 'id' => (string) $existing[1]->getRouteKey()]);

        /** Add a duplicate to add: this should only be added once. */
        $data->push(['type' => 'roles', 'id' => (string) $add[0]->getRouteKey()]);

        $response = $this
            ->jsonApi()
            ->expects('roles')
            ->withData($data)
            ->post(url('/api/v1/users', [$user, 'relationships', 'roles']));

        $response->assertNoContent();

        $this->assertDatabaseCount('role_user', count($existing) + count($add));

        foreach ($existing->merge($add) as $role) {
            $this->assertDatabaseHas('role_user', [
                'user_id' => $user->getKey(),
                'role_id' => $role->getKey(),
            ]);
        }
    }

    public function testRemoveFromRelationship(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->roles()->saveMany($roles = factory(Role::class, 5)->create());

        $remove = $roles->take(3);

        $data = $remove->map(function (Role $role) {
            return ['type' => 'roles', 'id' => (string) $role->getRouteKey()];
        })->all();

        $response = $this
            ->jsonApi()
            ->expects('roles')
            ->withData($data)
            ->delete(url('/api/v1/users', [$user, 'relationships', 'roles']));

        $response->assertNoContent();

        $this->assertDatabaseCount('role_user', count($roles) - count($remove));

        foreach ($remove as $role) {
            $this->assertDatabaseMissing('role_user', [
                'user_id' => $user->getKey(),
                'role_id' => $role->getKey(),
            ]);
        }

        foreach ($roles->diff($remove) as $role) {
            $this->assertDatabaseHas('role_user', [
                'user_id' => $user->getKey(),
                'role_id' => $role->getKey(),
            ]);
        }
    }
}
