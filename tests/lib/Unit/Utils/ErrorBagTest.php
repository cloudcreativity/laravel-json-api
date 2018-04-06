<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Utils;

use CloudCreativity\LaravelJsonApi\Document\Error;
use CloudCreativity\LaravelJsonApi\Utils\ErrorBag;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\TestCase;

/**
 * Class ErrorBagTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ErrorBagTest extends TestCase
{

    public function testIteration()
    {
        $bag = ErrorBag::create(new MessageBag([
            'foo' => 'foobar',
            'bar' => [
                'baz',
                'bat',
            ],
        ]));

        $expected = [
            (new Error())->setSourcePointer('foo')->setDetail('foobar'),
            (new Error())->setSourcePointer('bar')->setDetail('baz'),
            (new Error())->setSourcePointer('bar')->setDetail('bat'),
        ];

        $this->assertEquals($expected, $bag->toArray());
        $this->assertEquals(3, count($bag));
        $this->assertFalse($bag->isEmpty());
    }

    public function testPrototype()
    {
        $prototype = (new Error())->setTitle('Invalid Attribute')->setStatus(422);
        $expected = clone $prototype;
        $expected->setSourcePointer('foo')->setDetail('Some detail');
        $messages = new MessageBag(['foo' => 'Some detail']);
        $bag = ErrorBag::create($messages, $prototype);

        $this->assertEquals($expected, current($bag->toArray()));
    }

    public function testSourcePointerPrefix()
    {
        $messages = new MessageBag(['foo' => 'Some detail']);
        $expected = (new Error())->setSourcePointer('/data/attributes/foo')->setDetail('Some detail');

        $bag = new ErrorBag($messages, null, '/data/attributes');
        $this->assertEquals($expected, current($bag->toArray()));

        $bag = ErrorBag::create($messages)->withSourcePrefix('/data/attributes');
        $this->assertEquals($expected, current($bag->toArray()));
    }

    public function testSourcePointerConvertsDotNotation()
    {
        $messages = new MessageBag(['foo.bar.baz' => 'Some detail']);
        $expected = new Error();
        $expected->setSourcePointer('/data/attributes/foo/bar/baz')->setDetail('Some detail');
        $bag = new ErrorBag($messages, null, '/data/attributes');
        $this->assertEquals($expected, current($bag->toArray()));
    }

    public function testSourcePointerDasherizesKeys()
    {
        $messages = new MessageBag(['foo_bar.baz_bat' => 'Some detail']);
        $expected = new Error();
        $expected->setSourcePointer('/data/attributes/foo-bar/baz-bat')->setDetail('Some detail');

        $bag = ErrorBag::create($messages)
            ->withSourcePrefix('/data/attributes')
            ->withDasherizedKeys();

        $this->assertEquals($expected, current($bag->toArray()));
    }

    /**
     * @return array
     */
    public function mappedPointersProvider()
    {
        return [
            ['category', ['foo' => 'bar'], '/category'],
            ['type', ['type' => 'category'], '/category'],
            ['data.foo', ['data.foo' => 'config.foo_bar'], '/config/foo-bar'],
            ['data.*', ['data.*' => 'config'], '/config'],
            ['data', function ($key) {
                return 'config';
            }, '/config'],
        ];
    }

    /**
     * @param $key
     * @param $mapping
     * @param $expected
     * @dataProvider mappedPointersProvider
     */
    public function testSourcePointerMapsKeys($key, $mapping, $expected)
    {
        $messages = new MessageBag([$key => 'Some detail']);
        $error = new Error();
        $error->setSourcePointer("/data/attributes{$expected}")->setDetail('Some detail');

        $bag = ErrorBag::create($messages)
            ->withSourcePrefix('/data/attributes')
            ->withKeyMap($mapping)
            ->withDasherizedKeys();

        $this->assertEquals($error, current($bag->toArray()));
    }

    public function testSourceParameter()
    {
        $messages = new MessageBag(['foo.bar.baz' => 'Some detail']);
        $expected = new Error();
        $expected->setSourceParameter('filter.foo.bar.baz')->setDetail('Some detail');

        $bag = new ErrorBag($messages, null, 'filter', true);
        $this->assertEquals($expected, current($bag->toArray()));

        $bag = ErrorBag::create($messages)->withSourcePrefix('filter')->withParameters();
        $this->assertEquals($expected, current($bag->toArray()));
    }

    public function testSourceParameterDasherizesKeys()
    {
        $messages = new MessageBag(['foo_bar.baz_bat' => 'Some detail']);
        $expected = new Error();
        $expected->setSourceParameter('filter.foo-bar.baz-bat')->setDetail('Some detail');

        $bag = ErrorBag::create($messages)
            ->withSourcePrefix('filter')
            ->withDasherizedKeys()
            ->withParameters();

        $this->assertEquals($expected, current($bag->toArray()));
    }
}
