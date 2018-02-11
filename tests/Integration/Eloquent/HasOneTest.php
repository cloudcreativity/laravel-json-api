<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Phone;
use CloudCreativity\LaravelJsonApi\Tests\Models\User;

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
     * @var string
     */
    protected $resourceType = 'users';

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
            ],
            'relationships' => [
                'phone' => [
                    'data' => null,
                ],
            ],
        ];

        $expected = $data;
        unset($expected['attributes']['password']);

        $id = $this->doCreate($data)->assertCreatedWithId($expected);

        $this->assertNotNull($refreshed = User::find($id));
        $this->assertNull($refreshed->phone);
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
        unset($expected['attributes']['password']);

        $id = $this->doCreate($data)->assertCreatedWithId($expected);

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

        $this->doUpdate($data)->assertUpdated($data);

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

        $this->doUpdate($data)->assertUpdated($data);

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

        $this->doUpdate($data)->assertUpdated($data);

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
            'id' => $phone->getKey(),
            'attributes' => [
                'number' => $phone->number,
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => $user->getKey(),
                    ],
                ],
            ],
        ];

        $this->doReadRelated($user, 'phone')->assertReadHasOne($data);
    }

    /**
     * Test that we can read the resource identifier for the related phone.
     */
    public function testReadRelationship()
    {
        /** @var Phone $phone */
        $phone = factory(Phone::class)->states('user')->create();

        $this->doReadRelationship($phone->user, 'phone')
            ->assertReadHasOneIdentifier('phones', $phone->getKey());
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

        $data = ['type' => 'phones', 'id' => (string) $phone->getKey()];

        $this->doReplaceRelationship($user, 'phone', $data)
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

        $this->doReplaceRelationship($phone->user, 'phone', null)
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

        $data = ['type' => 'phones', 'id' => (string) $other->getKey()];

        $this->doReplaceRelationship($existing->user, 'phone', $data)
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
