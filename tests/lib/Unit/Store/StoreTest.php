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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Store;

use CloudCreativity\LaravelJsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Adapter\ResourceAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RecordNotFoundException;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifierCollection;
use CloudCreativity\LaravelJsonApi\Object\ResourceObject;
use CloudCreativity\LaravelJsonApi\Pagination\Page;
use CloudCreativity\LaravelJsonApi\Store\Store;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use CloudCreativity\Utils\Object\StandardObject;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class StoreTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class StoreTest extends TestCase
{

    /**
     * @var ContainerInterface|MockObject
     */
    private $container;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * A query request must be handed off to the adapter for the resource type
     * specified.
     */
    public function testQuery()
    {
        $params = new EncodingParameters();
        $expected = new Page([]);

        $store = $this->store([
            'posts' => $this->willNotQuery(),
            'users' => $this->willQuery($params, $expected),
        ]);

        $this->assertSame($expected, $store->queryRecords('users', $params));
    }

    /**
     * If there is no adapter for the resource type, an exception must be thrown.
     */
    public function testCannotQuery()
    {
        $store = $this->store(['posts' => $this->willNotQuery()]);
        $this->expectException(RuntimeException::class);
        $store->queryRecords('users', new EncodingParameters());
    }

    public function testCreateRecord()
    {
        $document = ['foo' => 'bar'];

        $params = new EncodingParameters();
        $expected = new \stdClass();

        $store = $this->store([
            'posts' => $this->willNotQuery(),
            'comments' => $this->willCreateRecord($document, $params, $expected)
        ]);

        $this->assertSame($expected, $store->createRecord('comments', $document, $params));
    }

    public function testCannotCreate()
    {
        $store = $this->store(['posts' => $this->willNotQuery()]);
        $this->expectException(RuntimeException::class);
        $store->createRecord('comments', [], new EncodingParameters());
    }

    /**
     * A query record request must be handed off to the adapter for the resource type
     * specified in the identifier.
     */
    public function testReadRecord()
    {
        $params = new EncodingParameters();
        $expected = new \stdClass();

        $store = $this->storeByTypes([
            \DateTime::class => $this->willNotQuery(),
            \stdClass::class => $this->willReadRecord($expected, $params),
        ]);

        $this->assertSame($expected, $store->readRecord($expected, $params));
    }

    public function testUpdateRecord()
    {
        $params = new EncodingParameters();
        $document = ['foo' => 'bar'];
        $record = new \stdClass();
        $expected = clone $record;

        $adapter = $this->willUpdateRecord($record, $document, $params, $expected);

        $store = $this->storeByTypes([
            \DateTime::class => $this->willNotQuery(),
            \stdClass::class => $adapter,
        ]);

        $this->assertSame($expected, $store->updateRecord($record, $document, $params));
    }

    public function testDeleteRecord()
    {
        $params = new EncodingParameters();
        $record = new StandardObject();

        $adapter = $this->willDeleteRecord($record, $params);

        $store = $this->storeByTypes([
            ResourceObject::class => $this->willNotQuery(),
            StandardObject::class => $adapter,
        ]);

        $this->assertNull($store->deleteRecord($record, $params));
    }

    public function testDeleteRecordFails()
    {
        $params = new EncodingParameters();
        $record = new StandardObject();

        $adapter = $this->willDeleteRecord($record, $params, false);

        $store = $this->storeByTypes([
            ResourceObject::class => $this->willNotQuery(),
            StandardObject::class => $adapter,
        ]);

        $this->expectException(RuntimeException::class);
        $store->deleteRecord($record, $params);
    }

    /**
     * A query related request must be handed off to the relationship adapter provided by the
     * resource adapter.
     */
    public function testQueryRelated()
    {
        $parameters = new EncodingParameters();
        $record = new StandardObject();
        $expected = new Page([]);

        $store = $this->storeByTypes([
            ResourceObject::class => $this->willNotQuery(),
            StandardObject::class => $this->willQueryRelated($record, 'user', $parameters, $expected),
        ]);

        $this->assertSame($expected, $store->queryRelated($record, 'user', $parameters));
    }

    /**
     * A query relationship request must be handed off to the relationship adapter provided by
     * the resource adapter.
     */
    public function testQueryRelationship()
    {
        $parameters = new EncodingParameters();
        $record = new StandardObject();
        $expected = new Page([]);

        $store = $this->storeByTypes([
            ResourceObject::class => $this->willNotQuery(),
            StandardObject::class => $this->willQueryRelationship($record, 'user', $parameters, $expected),
        ]);

        $this->assertSame($expected, $store->queryRelationship($record, 'user', $parameters));
    }

    public function testExists()
    {
        $store = $this->store([
            'posts' => $this->adapter(),
            'users' => $this->willExist('99')
        ]);

        $this->assertTrue($store->isType('users'));
        $this->assertTrue($store->exists('users', '99'));
    }

    public function testExistsWithIdentifier()
    {
        $identifier = ResourceIdentifier::create('users', '99');

        $store = $this->store([
            'posts' => $this->adapter(),
            'users' => $this->willExist('99')
        ]);

        $this->assertTrue($store->isType('users'));
        $this->assertTrue($store->exists($identifier));
    }

    public function testDoesNotExist()
    {
        $identifier = ResourceIdentifier::create('users', '99');

        $store = $this->store([
            'posts' => $this->adapter(),
            'users' => $this->willNotExist('99')
        ]);

        $this->assertTrue($store->isType('users'));
        $this->assertFalse($store->exists($identifier));
    }

    public function testCannotDetermineExistence()
    {
        $identifier = ResourceIdentifier::create('users', '99');
        $store = $this->store(['posts' => $this->adapter()]);

        $this->assertFalse($store->isType('users'));
        $this->expectException(RuntimeException::class);
        $store->exists($identifier);
    }

    public function testFind()
    {
        $expected = new \stdClass();

        $store = $this->store([
            'posts' => $this->adapter(),
            'users' => $this->willFind('99', $expected)
        ]);

        $this->assertSame($expected, $store->find('users', '99'));
    }

    public function testFindWithIdentifier()
    {
        $identifier = ResourceIdentifier::create('users', '99');
        $expected = new StandardObject();

        $store = $this->store([
            'posts' => $this->adapter(),
            'users' => $this->willFind('99', $expected)
        ]);

        $this->assertSame($expected, $store->find($identifier));
        $this->assertSame($expected, $store->findOrFail($identifier));
    }

    public function testCannotFind()
    {
        $identifier = ResourceIdentifier::create('users', '99');

        $store = $this->store([
            'posts' => $this->adapter(),
            'users' => $this->willNotFind('99')
        ]);

        $this->assertNull($store->find($identifier));
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionMessage('users:99');
        $store->findOrFail($identifier);
    }

    /**
     * If exists is called multiple times, we expect the adapter to only be queried once.
     */
    public function testExistsCalledOnce()
    {
        $identifier = ResourceIdentifier::create('users', '99');

        $store = $this->store([
            'posts' => $this->adapter(),
            'users' => $this->willExist('99', true, $this->once())
        ]);

        $this->assertTrue($store->exists($identifier));
        $this->assertTrue($store->exists($identifier));
    }

    /**
     * If find is called multiple times, we expected the adapter to only be queried once.
     */
    public function testFindCalledOnce()
    {
        $identifier = ResourceIdentifier::create('users', '99');
        $expected = new StandardObject();

        $store = $this->store([
            'posts' => $this->adapter(),
            'users' => $this->willFind('99', $expected, $this->once()),
        ]);

        $this->assertSame($expected, $store->find($identifier));
        $this->assertSame($expected, $store->find($identifier));
    }

    /**
     * If find returns the objects, then exists is called, the adapter does not need to be queried
     * because the store already knows that it exists.
     */
    public function testFindBeforeExists()
    {
        $identifier = ResourceIdentifier::create('users', '99');
        $expected = new StandardObject();

        $mock = $this->adapter();
        $mock->expects($this->never())->method('exists');
        $mock->method('find')->with('99')->willReturn($expected);

        $store = $this->store(['users' => $mock]);
        $this->assertSame($expected, $store->find($identifier));
        $this->assertTrue($store->exists($identifier));
    }

    /**
     * If find does not return the object, then exists is called, the adapter does not need to be
     * queried because the store already knows that it does not exist.
     */
    public function testFindNoneBeforeExists()
    {
        $identifier = ResourceIdentifier::create('users', '99');

        $mock = $this->adapter();
        $mock->expects($this->never())->method('exists');

        $store = $this->store(['users' => $mock]);
        $this->assertNull($store->find($identifier));
        $this->assertFalse($store->exists($identifier));
    }

    /**
     * If exists returns false and then find is called, null should be returned without the adapter
     * being queried because the store already knows it does not exist.
     */
    public function testDoesNotExistBeforeFind()
    {
        $identifier = ResourceIdentifier::create('users', '99');

        $mock = $this->adapter();
        $mock->expects($this->once())->method('exists')->with('99')->willReturn(false);
        $mock->expects($this->never())->method('find');

        $store = $this->store(['users' => $mock]);
        $this->assertFalse($store->exists($identifier));
        $this->assertNull($store->find($identifier));
    }

    /**
     * A find many request hands the ids off to the adapter of each resource type,
     * and returns an empty array if no records are found.
     */
    public function testFindManyReturnsEmpty()
    {
        $identifiers = ResourceIdentifierCollection::create([
            (object) ['type' => 'posts', 'id' => '1'],
            (object) ['type' => 'users', 'id' => '99'],
            (object) ['type' => 'posts', 'id' => '3'],
        ]);

        $store = $this->store([
            'posts' => $this->willFindMany(['1', '3']),
            'users' => $this->willFindMany(['99']),
            'tags' => $this->willNotFindMany(),
        ]);

        $this->assertSame([], $store->findMany($identifiers));
    }

    /**
     * A find many request hands the ids off to the adapter of each resource type,
     * and returns an array containing all found records.
     */
    public function testFindMany()
    {
        $post = (object) ['foo' => 'bar'];
        $user = (object) ['baz' => 'bat'];

        $identifiers = [
            ['type' => 'posts', 'id' => '1'],
            ['type' => 'posts', 'id' => '3'],
            ['type' => 'users', 'id' => '99'],
        ];

        $store = $this->store([
            'posts' => $this->willFindMany(['1', '3'], [$post]),
            'users' => $this->willFindMany(['99'], [$user]),
            'tags' => $this->willNotFindMany(),
        ]);

        $this->assertSame([$post, $user], $store->findMany($identifiers));
    }

    /**
     * A find many request hands the ids off to the adapter of each resource type,
     * and returns an array containing all found records.
     */
    public function testFindManyWithIdentifiers()
    {
        $identifiers = ResourceIdentifierCollection::create([
            $post = (object) ['type' => 'posts', 'id' => '1'],
            (object) ['type' => 'posts', 'id' => '3'],
            $user = (object) ['type' => 'users', 'id' => '99'],
        ]);

        $store = $this->store([
            'posts' => $this->willFindMany(['1', '3'], [$post]),
            'users' => $this->willFindMany(['99'], [$user]),
            'tags' => $this->willNotFindMany(),
        ]);

        $this->assertSame([$post, $user], $store->findMany($identifiers));
    }

    /**
     * An exception is thrown if a resource type in the find many identifiers
     * is not recognised.
     */
    public function testCannotFindMany()
    {
        $identifiers = ResourceIdentifierCollection::create([
            $post = (object) ['type' => 'posts', 'id' => '1'],
            (object) ['type' => 'posts', 'id' => '3'],
            $user = (object) ['type' => 'users', 'id' => '99'],
        ]);

        $store = $this->store([
            'posts' => $this->willFindMany(['1', '3']),
        ]);

        $this->expectException(RuntimeException::class);
        $store->findMany($identifiers);
    }

    /**
     * @param array $adapters
     * @return StoreInterface
     */
    private function store(array $adapters)
    {
        $this->container
            ->method('getAdapterByResourceType')
            ->willReturnCallback(function ($resourceType) use ($adapters) {
                return isset($adapters[$resourceType]) ? $adapters[$resourceType] : null;
            });

        return new Store($this->container);
    }

    /**
     * @param array $types
     * @return StoreInterface
     */
    private function storeByTypes(array $types)
    {
        $this->container
            ->method('getAdapter')
            ->willReturnCallback(function ($object) use ($types) {
                $type = get_class($object);
                return isset($types[$type]) ? $types[$type] : null;
            });

        $this->container
            ->method('getAdapterByType')
            ->willReturnCallback(function ($type) use ($types) {
                return isset($types[$type]) ? $types[$type] : null;
            });

        return new Store($this->container);
    }

    /**
     * @param string $resourceId
     * @param bool $exists
     * @param $expectation
     * @return MockObject
     */
    private function willExist($resourceId, $exists = true, $expectation = null)
    {
        $expectation = $expectation ?: $this->any();

        $mock = $this->adapter();
        $mock->expects($expectation)
            ->method('exists')
            ->with($resourceId)
            ->willReturn($exists);

        return $mock;
    }

    /**
     * @param $resourceId
     * @param $expectation
     * @return MockObject
     */
    private function willNotExist($resourceId, $expectation = null)
    {
        return $this->willExist($resourceId, false, $expectation);
    }

    /**
     * @param $resourceId
     * @param $record
     * @param $expectation
     * @return MockObject
     */
    private function willFind($resourceId, $record, $expectation = null)
    {
        $mock = $this->adapter();
        $mock->expects($expectation ?: $this->any())
            ->method('find')
            ->with($resourceId)
            ->willReturn($record);

        return $mock;
    }

    /**
     * @param $resourceId
     * @param $expectation
     * @return MockObject
     */
    private function willNotFind($resourceId, $expectation = null)
    {
        return $this->willFind($resourceId, null, $expectation);
    }

    /**
     * @param array $resourceIds
     * @param array $results
     * @return MockObject
     */
    private function willFindMany(array $resourceIds, array $results = [])
    {
        $mock = $this->adapter();
        $mock->expects($this->atLeastOnce())
            ->method('findMany')
            ->with($resourceIds)
            ->willReturn($results);

        return $mock;
    }

    /**
     * @return MockObject
     */
    private function willNotFindMany()
    {
        $mock = $this->adapter();
        $mock->expects($this->never())->method('findMany');

        return $mock;
    }

    /**
     * @param $params
     * @param $results
     * @param null $expectation
     * @return MockObject
     */
    private function willQuery($params, $results, $expectation = null)
    {
        $mock = $this->adapter();

        $mock->expects($expectation ?: $this->any())
            ->method('query')
            ->with($params)
            ->willReturn($results);

        return $mock;
    }

    /**
     * @param $document
     * @param $params
     * @param $expected
     * @return MockObject
     */
    private function willCreateRecord($document, $params, $expected)
    {
        $mock = $this->adapter();

        $mock->expects($this->once())
            ->method('create')
            ->with($document, $params)
            ->willReturn($expected);

        return $mock;
    }

    /**
     * @param $record
     * @param $params
     * @return MockObject
     */
    private function willReadRecord($record, $params)
    {
        $mock = $this->adapter();

        $mock->expects($this->atLeastOnce())
            ->method('read')
            ->with($record, $params)
            ->willReturn($record);

        return $mock;
    }

    /**
     * @param $record
     * @param $resourceObject
     * @param $params
     * @param $expected
     * @return MockObject
     */
    private function willUpdateRecord($record, $resourceObject, $params, $expected)
    {
        $mock = $this->adapter();

        $mock->expects($this->once())
            ->method('update')
            ->with($record, $resourceObject, $params)
            ->willReturn($expected);

        return $mock;
    }

    /**
     * @param $record
     * @param $params
     * @param bool $result
     * @return MockObject
     */
    private function willDeleteRecord($record, $params, $result = true)
    {
        $mock = $this->adapter();

        $mock->expects($this->once())
            ->method('delete')
            ->with($record, $params)
            ->willReturn($result);

        return $mock;
    }

    /**
     * @param $record
     * @param $relationshipName
     * @param $parameters
     * @param $expected
     * @return MockObject
     */
    private function willQueryRelated($record, $relationshipName, $parameters, $expected)
    {
        $mock = $this->relationshipAdapter();

        $mock->expects($this->atLeastOnce())
            ->method('query')
            ->with($record, $parameters)
            ->willReturn($expected);

        return $this->adapter([$relationshipName => $mock]);
    }

    /**
     * @param $record
     * @param $relationshipName
     * @param $parameters
     * @param $expected
     * @return MockObject
     */
    private function willQueryRelationship($record, $relationshipName, $parameters, $expected)
    {
        $mock = $this->relationshipAdapter();
        $mock->expects($this->atLeastOnce())
            ->method('relationship')
            ->with($record, $parameters)
            ->willReturn($expected);

        return $this->adapter([$relationshipName => $mock]);
    }

    /**
     * @return MockObject
     */
    private function willNotQuery()
    {
        $mock = $this->adapter();
        $mock->expects($this->never())->method('query');
        $mock->expects($this->never())->method('create');
        $mock->expects($this->never())->method('read');
        $mock->expects($this->never())->method('update');
        $mock->expects($this->never())->method('delete');
        $mock->expects($this->never())->method('getRelated');

        return $mock;
    }

    /**
     * @param array $relationships
     * @return MockObject
     */
    private function adapter(array $relationships = [])
    {
        $mock = $this->createMock(ResourceAdapterInterface::class);

        $mock->method('getRelated')->willReturnCallback(function ($name) use ($relationships) {
            return $relationships[$name];
        });

        return $mock;
    }

    /**
     * @return MockObject
     */
    private function relationshipAdapter()
    {
        return $this->createMock(RelationshipAdapterInterface::class);
    }
}
