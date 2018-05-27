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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use DummyApp\Country;
use DummyApp\Phone;
use DummyApp\User;

/**
 * Class HasManyTest
 *
 * Test a JSON API has-many relationship that relates to an Eloquent
 * has-many relationship.
 *
 * In our dummy app, this is the users relationship on a country model.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class HasManyTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'countries';

    public function testCreateWithEmpty()
    {
        /** @var Country $country */
        $country = factory(Country::class)->make();

        $data = [
            'type' => 'countries',
            'attributes' => [
                'name' => $country->name,
                'code' => $country->code,
            ],
            'relationships' => [
                'users' => [
                    'data' => [],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $id = $this->doCreate($data)->assertCreatedWithId($expected);

        $this->assertDatabaseMissing('users', [
            'country_id' => $id,
        ]);
    }

    public function testCreateWithRelated()
    {
        /** @var Country $country */
        $country = factory(Country::class)->make();
        $user = factory(User::class)->create();

        $data = [
            'type' => 'countries',
            'attributes' => [
                'name' => $country->name,
                'code' => $country->code,
            ],
            'relationships' => [
                'users' => [
                    'data' => [
                        [
                            'type' => 'users',
                            'id' => (string) $user->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']);

        $id = $this
            ->doCreate($data)
            ->assertCreatedWithId($expected);

        $this->assertUserIs(Country::find($id), $user);
    }

    public function testCreateWithManyRelated()
    {
        /** @var Country $country */
        $country = factory(Country::class)->make();
        $users = factory(User::class, 2)->create();

        $data = [
            'type' => 'countries',
            'attributes' => [
                'name' => $country->name,
                'code' => $country->code,
            ],
            'relationships' => [
                'users' => [
                    'data' => [
                        [
                            'type' => 'users',
                            'id' => (string) $users->first()->getKey(),
                        ],
                        [
                            'type' => 'users',
                            'id' => (string) $users->last()->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']['users']);

        $id = $this
            ->doCreate($data)
            ->assertCreatedWithId($expected);

        $this->assertUsersAre(Country::find($id), $users);
    }

    public function testUpdateReplacesRelationshipWithEmptyRelationship()
    {
        /** @var Country $country */
        $country = factory(Country::class)->create();
        $users = factory(User::class, 2)->create();
        $country->users()->saveMany($users);

        $data = [
            'type' => 'countries',
            'id' => (string) $country->getKey(),
            'relationships' => [
                'users' => [
                    'data' => [],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']['users']);

        $this->doUpdate($data)->assertUpdated($expected);

        $this->assertDatabaseMissing('users', [
            'country_id' => $country->getKey(),
        ]);
    }

    public function testUpdateReplacesEmptyRelationshipWithResource()
    {
        /** @var Country $country */
        $country = factory(Country::class)->create();
        $user = factory(User::class)->create();

        $data = [
            'type' => 'countries',
            'id' => (string) $country->getKey(),
            'relationships' => [
                'users' => [
                    'data' => [
                        [
                            'type' => 'users',
                            'id' => (string) $user->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']['users']);

        $this->doUpdate($data)->assertUpdated($expected);
        $this->assertUserIs($country, $user);
    }

    public function testUpdateChangesRelatedResources()
    {
        /** @var Country $country */
        $country = factory(Country::class)->create();
        $country->users()->saveMany(factory(User::class, 3)->create());

        $users = factory(User::class, 2)->create();

        $data = [
            'type' => 'countries',
            'id' => (string) $country->getKey(),
            'relationships' => [
                'users' => [
                    'data' => [
                        [
                            'type' => 'users',
                            'id' => (string) $users->first()->getKey(),
                        ],
                        [
                            'type' => 'users',
                            'id' => (string) $users->last()->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $expected = $data;
        unset($expected['relationships']['users']);

        $this->doUpdate($data)->assertUpdated($expected);
        $this->assertUsersAre($country, $users);
    }

    public function testReadRelated()
    {
        /** @var Country $country */
        $country = factory(Country::class)->create();
        $users = factory(User::class, 2)->create();

        $country->users()->saveMany($users);

        $this->doReadRelated($country, 'users')
            ->assertReadHasMany('users', $users);
    }

    public function testReadRelatedEmpty()
    {
        /** @var Country $country */
        $country = factory(Country::class)->create();

        $this->doReadRelated($country, 'users')
            ->assertReadHasMany(null);
    }

    public function testReadRelatedWithFilter()
    {
        $country = factory(Country::class)->create();

        $a = factory(User::class)->create([
            'name' => 'John Doe',
            'country_id' => $country->getKey(),
        ]);

        $b = factory(User::class)->create([
            'name' => 'Jane Doe',
            'country_id' => $country->getKey(),
        ]);

        factory(User::class)->create([
            'name' => 'Frankie Manning',
            'country_id' => $country->getKey(),
        ]);

        $this->doReadRelated($country, 'users', ['filter' => ['name' => 'Doe']])
            ->assertReadHasMany('users', [$a, $b]);
    }

    public function testReadRelatedWithInvalidFilter()
    {
        $country = factory(Country::class)->create();

        $this->doReadRelated($country, 'users', ['filter' => ['name' => '']])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('filter.name');
    }

    public function testReadRelatedWithSort()
    {
        $country = factory(Country::class)->create();

        $a = factory(User::class)->create([
            'name' => 'John Doe',
            'country_id' => $country->getKey(),
        ]);

        $b = factory(User::class)->create([
            'name' => 'Jane Doe',
            'country_id' => $country->getKey(),
        ]);

        $this->doReadRelated($country, 'users', ['sort' => 'name'])
            ->assertReadHasMany('users', [$b, $a]);
    }

    public function testReadRelatedWithInvalidSort()
    {
        $country = factory(Country::class)->create();

        // code is a valid sort on the countries resource, but not on the users resource.
        $this->doReadRelated($country, 'users', ['sort' => 'code'])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('sort');
    }

    public function testReadRelatedWithInclude()
    {
        $country = factory(Country::class)->create();
        $users = factory(User::class, 2)->create();
        $country->users()->saveMany($users);
        $phone = factory(Phone::class)->create(['user_id' => $users[0]->getKey()]);

        $response = $this
            ->doReadRelated($country, 'users', ['include' => 'phone'])
            ->assertReadHasMany('users', $users);

        $response->assertDocument()
            ->assertIncluded()
            ->assertContainsOnly(['phones' => [$phone->getKey()]]);
    }

    public function testReadRelatedWithInvalidInclude()
    {
        $country = factory(Country::class)->create();

        $this->doReadRelated($country, 'users', ['include' => 'foo'])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('include');
    }

    public function testReadRelatedWithPagination()
    {
        $country = factory(Country::class)->create();
        $users = factory(User::class, 3)->create();
        $country->users()->saveMany($users);

        $response = $this
            ->doReadRelated($country, 'users', ['page' => ['number' => 1, 'size' => 2]])
            ->assertReadHasMany('users', $users->take(2));

        $response->assertDocument()
            ->assertMetaSubset(['page' => ['current-page' => 1, 'per-page' => 2]]);
    }

    public function testReadRelatedWithInvalidPagination()
    {
        $country = factory(Country::class)->create();

        $this->doReadRelated($country, 'users', ['page' => ['number' => 0, 'size' => 10]])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('page.number');
    }

    public function testReadRelationship()
    {
        $country = factory(Country::class)->create();
        $users = factory(User::class, 2)->create();
        $country->users()->saveMany($users);

        $this->doReadRelationship($country, 'users')
            ->assertReadHasManyIdentifiers('users', $users);
    }

    public function testReadEmptyRelationship()
    {
        $country = factory(Country::class)->create();

        $this->doReadRelationship($country, 'users')
            ->assertReadHasManyIdentifiers(null);
    }

    public function testReplaceEmptyRelationshipWithRelatedResource()
    {
        $country = factory(Country::class)->create();
        $users = factory(User::class, 2)->create();

        $data = $users->map(function (User $user) {
            return ['type' => 'users', 'id' => (string) $user->getKey()];
        })->all();

        $this->doReplaceRelationship($country, 'users', $data)
            ->assertStatus(204);

        $this->assertUsersAre($country, $users);
    }

    public function testReplaceRelationshipWithNone()
    {
        $country = factory(Country::class)->create();
        $users = factory(User::class, 2)->create();
        $country->users()->saveMany($users);

        $this->doReplaceRelationship($country, 'users', [])
            ->assertStatus(204);

        $this->assertFalse($country->users()->exists());
    }

    public function testReplaceRelationshipWithDifferentResources()
    {
        $country = factory(Country::class)->create();
        $country->users()->saveMany(factory(User::class, 2)->create());

        $users = factory(User::class, 3)->create();

        $data = $users->map(function (User $user) {
            return ['type' => 'users', 'id' => (string) $user->getKey()];
        })->all();

        $this->doReplaceRelationship($country, 'users', $data)
            ->assertStatus(204);

        $this->assertUsersAre($country, $users);
    }

    public function testAddToRelationship()
    {
        $country = factory(Country::class)->create();
        $existing = factory(User::class, 2)->create();
        $country->users()->saveMany($existing);

        $add = factory(User::class, 2)->create();
        $data = $add->map(function (User $user) {
            return ['type' => 'users', 'id' => (string) $user->getKey()];
        })->all();

        $this->doAddToRelationship($country, 'users', $data)
            ->assertStatus(204);

        $this->assertUsersAre($country, $existing->merge($add));
    }

    public function testRemoveFromRelationship()
    {
        $country = factory(Country::class)->create();
        $users = factory(User::class, 4)->create([
            'country_id' => $country->getKey(),
        ]);

        $data = $users->take(2)->map(function (User $user) {
            return ['type' => 'users', 'id' => (string) $user->getKey()];
        })->all();

        $this->doRemoveFromRelationship($country, 'users', $data)
            ->assertStatus(204);

        $this->assertUsersAre($country, [$users->get(2), $users->get(3)]);
    }

    /**
     * @param $country
     * @param $user
     * @return void
     */
    private function assertUserIs(Country $country, User $user)
    {
        $this->assertUsersAre($country, [$user]);
    }

    /**
     * @param Country $country
     * @param iterable $users
     * @return void
     */
    private function assertUsersAre(Country $country, $users)
    {
        $this->assertSame(count($users), $country->users()->count());

        /** @var User $user */
        foreach ($users as $user) {
            $this->assertDatabaseHas('users', [
                'id' => $user->getKey(),
                'country_id' => $country->getKey(),
            ]);
        }
    }
}
