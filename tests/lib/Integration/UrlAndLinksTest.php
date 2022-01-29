<?php

/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use Neomerx\JsonApi\Document\Link;

/**
 * Class UrlTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class UrlAndLinksTest extends TestCase
{

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
     * @dataProvider urlProvider
     */
    public function testUrl($expected, $method, $resourceId = null, $relationship = null)
    {
        $url = json_api()->url();
        $args = $this->normalizeArgs($resourceId, $relationship);

        $this->assertSame("http://localhost$expected", call_user_func_array([$url, $method], $args));
    }

    /**
     * @param $expected
     * @param $method
     * @param $resourceId
     * @param $relationship
     * @dataProvider urlProvider
     */
    public function testLink($expected, $method, $resourceId = null, $relationship = null)
    {
        $links = json_api()->links();
        $expected = new Link("http://localhost$expected", null, true);
        $args = $this->normalizeArgs($resourceId, $relationship);

        $this->assertEquals($expected, call_user_func_array([$links, $method], $args));
    }

    /**
     * @param $expected
     * @param $method
     * @param $resourceId
     * @param $relationship
     * @dataProvider urlProvider
     */
    public function testLinkWithMeta($expected, $method, $resourceId = null, $relationship = null)
    {
        $meta = (object) ['foo' => 'bar'];
        $links = json_api()->links();
        $expected = new Link("http://localhost$expected", $meta, true);
        $args = $this->normalizeArgs($resourceId, $relationship);
        $args[] = $meta;

        $this->assertEquals($expected, call_user_func_array([$links, $method], $args));
    }

    /**
     * @param $resourceId
     * @param $relationship
     * @return array
     */
    private function normalizeArgs($resourceId, $relationship)
    {
        if (!$relationship) {
            $args = $resourceId ? ['posts', $resourceId] : ['posts'];
        } else {
            $args = ['posts', $resourceId, $relationship];
        }

        return $args;
    }
}
