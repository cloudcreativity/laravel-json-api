<?php

namespace CloudCreativity\LaravelJsonApi\Utils;

use CloudCreativity\LaravelJsonApi\TestCase;

class FqnTest extends TestCase
{

    /**
     * @return array
     */
    public function schemaProvider()
    {
        return [
            [
                'App\JsonApi\Posts\Schema',
                'posts',
                'App\JsonApi',
                true,
            ],
            [
                'App\JsonApi\Schemas\Post',
                'posts',
                'App\JsonApi',
                false,
            ],
            [
                'App\JsonApi\DanceEvents\Schema',
                'dance-events',
                'App\JsonApi',
                true,
            ],
            [
                'App\JsonApi\Schemas\DanceEvent',
                'dance-events',
                'App\JsonApi',
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
                'App\JsonApi\Posts\Adapter',
                'posts',
                'App\JsonApi',
                true,
            ],
            [
                'App\JsonApi\Adapters\Post',
                'posts',
                'App\JsonApi',
                false,
            ],
            [
                'App\JsonApi\DanceEvents\Adapter',
                'dance-events',
                'App\JsonApi',
                true,
            ],
            [
                'App\JsonApi\Adapters\DanceEvent',
                'dance-events',
                'App\JsonApi',
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
                'App\JsonApi\Posts\Authorizer',
                'posts',
                'App\JsonApi',
                true,
            ],
            [
                'App\JsonApi\Authorizers\Post',
                'posts',
                'App\JsonApi',
                false,
            ],
            [
                'App\JsonApi\DanceEvents\Authorizer',
                'dance-events',
                'App\JsonApi',
                true,
            ],
            [
                'App\JsonApi\Authorizers\DanceEvent',
                'dance-events',
                'App\JsonApi',
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
                'App\JsonApi\Posts\Validators',
                'posts',
                'App\JsonApi',
                true,
            ],
            [
                'App\JsonApi\Validators\Post',
                'posts',
                'App\JsonApi',
                false,
            ],
            [
                'App\JsonApi\DanceEvents\Validators',
                'dance-events',
                'App\JsonApi',
                true,
            ],
            [
                'App\JsonApi\Validators\DanceEvent',
                'dance-events',
                'App\JsonApi',
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
}
