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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Encoder;

use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Schema\SchemaProviderInterface;
use CloudCreativity\LaravelJsonApi\Encoder\DataAnalyser;
use DummyApp\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataAnalyserTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     */
    private $container;

    /**
     * @var DataAnalyser
     */
    private DataAnalyser $analyser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->analyser = new DataAnalyser($this->container);
    }

    public function testNull(): void
    {
        $this->container
            ->expects($this->never())
            ->method($this->anything());

        $this->assertNull($this->analyser->getRootObject(null));
        $this->assertEmpty($this->analyser->getIncludePaths(null));
    }

    public function testResource(): void
    {
        $model = $this->createMock(Post::class);

        $includePaths = $this->withIncludePaths($model);

        $this->assertSame($model, $this->analyser->getRootObject($model));
        $this->assertSame($includePaths, $this->analyser->getIncludePaths($model));
    }

    /**
     * @return array[]
     */
    public static function iteratorProvider(): array
    {
        return [
            'array' => [
                static function (object ...$objects): array {
                    return $objects;
                },
            ],
            'enumerable' => [
                static function (object ...$objects): Enumerable {
                    return Collection::make($objects);
                },
            ],
            'iterator aggregate' => [
                static function (object ...$objects): \IteratorAggregate {
                    return new class($objects) implements \IteratorAggregate {
                        public function __construct(private readonly array $objects)
                        {
                        }

                        public function getIterator(): \ArrayIterator
                        {
                            return new \ArrayIterator($this->objects);
                        }
                    };
                },
            ],
            'iterator' => [
                static function (object ...$objects): \Iterator {
                    return new \ArrayIterator($objects);
                },
            ],
        ];
    }

    /**
     * @param \Closure $scenario
     * @return void
     * @dataProvider iteratorProvider
     */
    public function testIterable(\Closure $scenario): void
    {
        $object1 = $this->createMock(Post::class);
        $object2 = $this->createMock(Post::class);
        $object3 = $this->createMock(Post::class);

        $data = $scenario($object1, $object2, $object3);
        $includePaths = $this->withIncludePaths($object1);

        $actual = $this->analyser->getRootObject($data);

        $this->assertSame($object1, $actual);
        $this->assertSame($includePaths, $this->analyser->getIncludePaths($data));

        if (!is_array($data)) {
            // the iterator needs to work when iterated over again.
            $this->assertSame([$object1, $object2, $object3], iterator_to_array($data));
        }
    }

    /**
     * @param \Closure $scenario
     * @return void
     * @dataProvider iteratorProvider
     */
    public function testEmptyIterable(\Closure $scenario): void
    {
        $data = $scenario();

        $this->container
            ->method('hasSchema')
            ->with($this->identicalTo($data))
            ->willReturn(false);

        $this->container
            ->expects($this->never())
            ->method('getSchema');

        $actual = $this->analyser->getRootObject($data);

        $this->assertNull($actual);
        $this->assertEmpty($this->analyser->getIncludePaths($data));

        if (!is_array($data)) {
            // the iterator needs to work when iterated over again.
            $this->assertEmpty(iterator_to_array($data));
        }
    }

    public function testGenerator(): void
    {
        $func = function (): \Generator {
            yield from [];
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Generators are not supported as resource collections.');

        $this->analyser->getRootObject($func());
    }

    /**
     * @param object $object
     * @return string[]
     */
    private function withIncludePaths(object $object): array
    {
        $this->container
            ->method('hasSchema')
            ->willReturnCallback(static fn ($value) => $object === $value);

        $this->container
            ->method('getSchema')
            ->with($this->identicalTo($object))
            ->willReturn($schema = $this->createMock(SchemaProviderInterface::class));

        $schema
            ->method('getIncludePaths')
            ->willReturn($includePaths = ['foo', 'bar', 'baz.bat']);

        return $includePaths;
    }
}