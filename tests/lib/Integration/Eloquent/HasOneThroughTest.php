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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\History;
use DummyApp\Supplier;
use DummyApp\User;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Class HasOneThroughTest
 *
 * Test a JSON API has-one-through relationship that relates to an Eloquent
 * has-one-through relationship.
 *
 * In our dummy app, this is the user-history relationship on a supplier model.
 *
 * This relationship is read-only because it does not make sense to
 * modify the relationship through the resource relationship. I.e. the
 * history resource would be created/modified etc.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class HasOneThroughTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'suppliers';

    /**
     * Test that we can read the related phone.
     */
    public function testReadRelated(): void
    {
        $this->checkSupported();

        $supplier = factory(Supplier::class)->create();
        $user = factory(User::class)->create(['supplier_id' => $supplier->getKey()]);
        $history = factory(History::class)->create(['user_id' => $user->getKey()]);

        $data = [
            'type' => 'histories',
            'id' => (string) $history->getRouteKey(),
            'attributes' => [
                'detail' => $history->detail,
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->getRouteKey(),
                    ],
                ],
            ],
        ];

        $this->withoutExceptionHandling()
            ->doReadRelated($supplier, 'user-history', ['include' => 'user'])
            ->assertFetchedOne($data);
    }

    public function testReadRelatedEmpty(): void
    {
        $this->checkSupported();

        $supplier = factory(Supplier::class)->create();

        $this->withoutExceptionHandling()
            ->doReadRelated($supplier, 'user-history')
            ->assertFetchedNull();
    }

    public function testReadRelationship(): void
    {
        $this->checkSupported();

        $supplier = factory(Supplier::class)->create();
        $user = factory(User::class)->create(['supplier_id' => $supplier->getKey()]);
        $history = factory(History::class)->create(['user_id' => $user->getKey()]);

        $this->withoutExceptionHandling()
            ->willSeeResourceType('histories')
            ->doReadRelationship($supplier, 'user-history')
            ->assertFetchedToOne($history);
    }

    public function testReadEmptyRelationship(): void
    {
        $this->checkSupported();

        $supplier = factory(Supplier::class)->create();

        $this->withoutExceptionHandling()
            ->doReadRelationship($supplier, 'user-history')
            ->assertFetchedNull();
    }

    /**
     * @return void
     * @todo remove when minimum Laravel version is 5.8.
     */
    private function checkSupported(): void
    {
        if (!class_exists(HasOneThrough::class)) {
            $this->markTestSkipped('Eloquent has-one-through not supported.');
        }
    }
}
