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

use CloudCreativity\LaravelJsonApi\Utils\Fqn;
use PHPUnit\Framework\TestCase;

/**
 * Class FqnTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class FqnTest extends TestCase
{

    /**
     * @return array
     */
    public function schemaProvider()
    {
        return [
            [
                'DummyApp\JsonApi\Posts\Schema',
                'posts',
                'DummyApp\JsonApi',
                true,
            ],
            [
                'DummyApp\JsonApi\Schemas\Post',
                'posts',
                'DummyApp\JsonApi',
                false,
            ],
            [
                'DummyApp\JsonApi\DanceEvents\Schema',
                'dance-events',
                'DummyApp\JsonApi',
                true,
            ],
            [
                'DummyApp\JsonApi\Schemas\DanceEvent',
                'dance-events',
                'DummyApp\JsonApi',
                false,
            ],
        ];
    }

    /**
     * @param $expected
     * @param $resourceType
     * @param $rootNamespace
     * @param $byResource
     * @dataProvider schemaProvider
     */
    public function testSchema($expected, $resourceType, $rootNamespace, $byResource)
    {
        $this->assertEquals($expected, Fqn::schema($resourceType, $rootNamespace, $byResource));
    }

    /**
     * @return array
     */
    public function adapterProvider()
    {
        return [
            [
                'DummyApp\JsonApi\Posts\Adapter',
                'posts',
                'DummyApp\JsonApi',
                true,
            ],
            [
                'DummyApp\JsonApi\Adapters\Post',
                'posts',
                'DummyApp\JsonApi',
                false,
            ],
            [
                'DummyApp\JsonApi\DanceEvents\Adapter',
                'dance-events',
                'DummyApp\JsonApi',
                true,
            ],
            [
                'DummyApp\JsonApi\Adapters\DanceEvent',
                'dance-events',
                'DummyApp\JsonApi',
                false,
            ],
        ];
    }

    /**
     * @param $expected
     * @param $resourceType
     * @param $rootNamespace
     * @param $byResource
     * @dataProvider adapterProvider
     */
    public function testAdapter($expected, $resourceType, $rootNamespace, $byResource)
    {
        $this->assertEquals($expected, Fqn::adapter($resourceType, $rootNamespace, $byResource));
    }

    /**
     * @return array
     */
    public function authorizerProvider()
    {
        return [
            [
                'DummyApp\JsonApi\Posts\Authorizer',
                'posts',
                'DummyApp\JsonApi',
                true,
            ],
            [
                'DummyApp\JsonApi\Authorizers\Post',
                'posts',
                'DummyApp\JsonApi',
                false,
            ],
            [
                'DummyApp\JsonApi\DanceEvents\Authorizer',
                'dance-events',
                'DummyApp\JsonApi',
                true,
            ],
            [
                'DummyApp\JsonApi\Authorizers\DanceEvent',
                'dance-events',
                'DummyApp\JsonApi',
                false,
            ],
        ];
    }

    /**
     * @param $expected
     * @param $resourceType
     * @param $rootNamespace
     * @param $byResource
     * @dataProvider authorizerProvider
     */
    public function testAuthorizer($expected, $resourceType, $rootNamespace, $byResource)
    {
        $this->assertEquals($expected, Fqn::authorizer($resourceType, $rootNamespace, $byResource));
    }

    /**
     * @return array
     */
    public function validatorsProvider()
    {
        return [
            [
                'DummyApp\JsonApi\Posts\Validators',
                'posts',
                'DummyApp\JsonApi',
                true,
            ],
            [
                'DummyApp\JsonApi\Validators\Post',
                'posts',
                'DummyApp\JsonApi',
                false,
            ],
            [
                'DummyApp\JsonApi\DanceEvents\Validators',
                'dance-events',
                'DummyApp\JsonApi',
                true,
            ],
            [
                'DummyApp\JsonApi\Validators\DanceEvent',
                'dance-events',
                'DummyApp\JsonApi',
                false,
            ],
        ];
    }

    /**
     * @param $expected
     * @param $resourceType
     * @param $rootNamespace
     * @param $byResource
     * @dataProvider validatorsProvider
     */
    public function testValidators($expected, $resourceType, $rootNamespace, $byResource)
    {
        $this->assertEquals($expected, Fqn::validators($resourceType, $rootNamespace, $byResource));
    }

    /**
     * @return array
     */
    public function hydratorProvider()
    {
        return [
            [
                'DummyApp\JsonApi\Posts\Hydrator',
                'posts',
                'DummyApp\JsonApi',
                true,
            ],
            [
                'DummyApp\JsonApi\Hydrators\Post',
                'posts',
                'DummyApp\JsonApi',
                false,
            ],
            [
                'DummyApp\JsonApi\DanceEvents\Hydrator',
                'dance-events',
                'DummyApp\JsonApi',
                true,
            ],
            [
                'DummyApp\JsonApi\Hydrators\DanceEvent',
                'dance-events',
                'DummyApp\JsonApi',
                false,
            ],
        ];
    }

    /**
     * @param $expected
     * @param $resourceType
     * @param $rootNamespace
     * @param $byResource
     * @dataProvider hydratorProvider
     */
    public function testHydrator($expected, $resourceType, $rootNamespace, $byResource)
    {
        $this->assertEquals($expected, Fqn::hydrator($resourceType, $rootNamespace, $byResource));
    }
}
