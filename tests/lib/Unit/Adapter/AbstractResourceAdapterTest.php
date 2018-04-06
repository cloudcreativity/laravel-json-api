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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Adapter;

use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use DateTime;
use DateTimeZone;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

/**
 * Class AbstractHydratorTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class AbstractResourceAdapterTest extends TestCase
{

    /**
     * @var TestAdapter
     */
    private $hydrator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $store;

    /**
     * @return void
     */
    protected function setUp()
    {
        /** @var StoreInterface $store */
        $store = $this->store = $this->createMock(StoreInterface::class);
        $this->hydrator = new TestAdapter();
        $this->hydrator->withStore($store);
    }

    public function testCreate()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "27f80377-c66b-4d35-8fd6-647e89e0c239",
        "attributes": {
            "title": "My First Post",
            "content": "Here is some content..."
        }
    }
}
JSON_API;

        $document = $this->decode($content);

        $expected = (object) [
            'id' => '27f80377-c66b-4d35-8fd6-647e89e0c239',
            'saved' => true,
            'title' => 'My First Post',
            'content' => 'Here is some content...',
        ];

        $record = $this->hydrator->create($document->getResource(), new EncodingParameters());
        $this->assertEquals($expected, $record);
    }

    public function testUpdate()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "My First Post",
            "content": "Here is some content..."
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $record = (object) ['id' => '1'];

        $expected = (object) [
            'id' => '1',
            'title' => 'My First Post',
            'content' => 'Here is some content...',
            'saved' => true,
        ];

        $this->hydrator->update($record, $document->getResource(), new EncodingParameters());
        $this->assertEquals($expected, $record);
    }

    public function testAttributeFieldMethodInvoked()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "my first post",
            "content": "Here is some content..."
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $record = (object) ['id' => '1'];

        $expected = (object) [
            'id' => '1',
            'title' => 'My First Post',
            'content' => 'Here is some content...',
            'saved' => true,
        ];

        $this->hydrator->update($record, $document->getResource(), new EncodingParameters());
        $this->assertEquals($expected, $record);
    }

    public function testAttributeAliases()
    {
        $this->hydrator->attributes = [
            'title',
            'content',
            'published' => 'is_published',
        ];

        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "My First Post",
            "content": "Here is some content...",
            "published": true
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $record = (object) ['id' => '1'];

        $expected = (object) [
            'id' => '1',
            'title' => 'My First Post',
            'content' => 'Here is some content...',
            'is_published' => true,
            'saved' => true,
        ];

        $this->hydrator->update($record, $document->getResource(), new EncodingParameters());
        $this->assertEquals($expected, $record);
    }

    public function testIgnoresAttributes()
    {
        $this->hydrator->attributes = ['title', 'content'];

        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "My First Post",
            "content": "Here is some content...",
            "published": true
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $record = (object) ['id' => '1'];

        $this->hydrator->update($record, $document->getResource(), new EncodingParameters());
        $this->assertObjectNotHasAttribute('published', $record);
    }

    /**
     * Test for date conversion
     *
     * - Dates to be specified using the `dates` attribute on the hydrator
     * - Should cast W3C date strings, including timezone.
     * - As Javascript will include milliseconds, these need to work too.
     * - Empty (`null`) values should be respected.
     */
    public function testConvertsDates()
    {
        $this->hydrator->attributes = [
            'exact',
            'published-at' => 'published_at',
            'empty',
        ];

        $this->hydrator->dates = ['exact', 'empty', 'published-at'];

        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "My First Post",
            "content": "Here is some content...",
            "published-at": "2017-07-01T12:30:00+01:00",
            "exact": "2017-07-10T13:00:00.150+10:00",
            "empty": null
        }
    }
}
JSON_API;

        $published = new DateTime('2017-07-01 12:30:00', new DateTimeZone('Europe/London'));
        $exact = new DateTime('2017-07-10 13:00:00.150', new DateTimeZone('Australia/Melbourne'));
        $document = $this->decode($content);
        $record = (object) ['id' => '1'];

        $this->hydrator->update($record, $document->getResource(), new EncodingParameters());
        $this->assertEquals($published, $record->published_at);
        $this->assertEquals($exact, $record->exact);
        $this->assertObjectHasAttribute('empty', $record);
        $this->assertNull($record->empty);
    }

}
