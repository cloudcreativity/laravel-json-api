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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Repositories;

use CloudCreativity\LaravelJsonApi\Repositories\SchemasRepository;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use Neomerx\JsonApi\Factories\Factory;

/**
 * Class SchemasRepositoryTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class SchemasRepositoryTest extends TestCase
{

    const A = 'foo';
    const B = 'bar';

    /**
     * @var array
     */
    private $config = [
        SchemasRepository::DEFAULTS => [
            'Author' => 'AuthorSchema',
            'Comment' => 'CommentSchema',
        ],
        self::A => [
            'Post' => 'PostSchema',
        ],
        self::B => [
            'Like' => 'LikeSchema',
        ],
    ];

    private $defaults;
    private $a;
    private $b;

    /**
     * @var SchemasRepository
     */
    private $repository;

    protected function setUp()
    {
        $factory = new Factory();

        $this->repository = new SchemasRepository($factory);
        $this->repository->configure($this->config);

        $defaults = $this->config[SchemasRepository::DEFAULTS];
        $this->defaults = $factory->createContainer($defaults);
        $this->a = $factory->createContainer(array_merge($defaults, $this->config[static::A]));
        $this->b = $factory->createContainer(array_merge($defaults, $this->config[static::B]));
    }

    public function testDefaults()
    {
        $this->assertEquals($this->defaults, $this->repository->getSchemas());
        $this->assertEquals($this->defaults, $this->repository->getSchemas(SchemasRepository::DEFAULTS));
    }

    public function testVariantA()
    {
        $this->assertEquals($this->a, $this->repository->getSchemas(static::A));
    }

    public function testVariantB()
    {
        $this->assertEquals($this->b, $this->repository->getSchemas(static::B));
    }

    /**
     * @depends testDefaults
     */
    public function testRootConfig()
    {
        $defaults = $this->config[SchemasRepository::DEFAULTS];

        $repository = new SchemasRepository(new Factory());
        $repository->configure($defaults);

        $this->assertEquals($this->defaults, $repository->getSchemas());

        $this->expectException(\RuntimeException::class);
        $repository->getSchemas('foo');
    }
}
