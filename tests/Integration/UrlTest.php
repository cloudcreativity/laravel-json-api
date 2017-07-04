<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;

class UrlTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->withDefaultApi([], function (ApiGroup $api) {
            $api->resource('posts', [
                'has-one' => 'author',
                'has-many' => 'comments',
            ]);
        });
    }

    /**
     * @return array
     */
    public function urlProvider()
    {
        return [
            ['/api/v1/posts', 'index'],
            ['/api/v1/posts', 'create'],
            ['/api/v1/posts/1', 'read', '1'],
            ['/api/v1/posts/1', 'update', '1'],
            ['/api/v1/posts/1', 'delete', '1'],
            ['/api/v1/posts/1/author', 'relatedResource', '1', 'author'],
            ['/api/v1/posts/1/relationships/author', 'readRelationship', '1', 'author'],
            ['/api/v1/posts/1/relationships/author', 'replaceRelationship', '1', 'author'],
            ['/api/v1/posts/1/relationships/comments', 'addRelationship', '1', 'comments'],
            ['/api/v1/posts/1/relationships/comments', 'replaceRelationship', '1', 'comments'],
        ];
    }

    /**
     * @param $expected
     * @param $method
     * @param $resourceId
     * @param $relationship
     * @param array $params
     * @dataProvider urlProvider
     */
    public function testUrl($expected, $method, $resourceId = null, $relationship = null, $params = [])
    {
        $url = json_api('default')->url();

        if (!$relationship) {
            $args = $resourceId ? ['posts', $resourceId, $params] : ['posts', $params];
        } else {
            $args = ['posts', $resourceId, $relationship, $params];
        }

        $this->assertSame("http://localhost$expected", call_user_func_array([$url, $method], $args));
    }
}
