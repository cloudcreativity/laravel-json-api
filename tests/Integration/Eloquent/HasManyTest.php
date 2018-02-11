<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Country;
use CloudCreativity\LaravelJsonApi\Tests\Models\User;

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

        $this->expectSuccess()
            ->doReplaceRelationship($country, 'users', [])
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

        $this->expectSuccess()
            ->doReplaceRelationship($country, 'users', $data)
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

        $this->expectSuccess()
            ->doAddToRelationship($country, 'users', $data)
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

        $this->expectSuccess()
            ->doRemoveFromRelationship($country, 'users', $data)
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
