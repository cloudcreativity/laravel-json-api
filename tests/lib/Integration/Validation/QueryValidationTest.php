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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Validation;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Country;

class QueryValidationTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @return array
     */
    public function searchProvider()
    {
        return [
            'fields:not allowed (singular)' => [
                ['fields' => ['posts' => 'title,foo']],
                'fields',
                'Sparse field set posts.foo is not allowed.',
            ],
            'fields:not allowed (plural)' => [
                ['fields' => ['posts' => 'title,foo,bar', 'users' => 'foobar']],
                'fields',
                'Sparse field sets posts.foo, posts.bar, users.foobar are not allowed.',
            ],
            'filter:invalid' => [
                ['filter' => ['title' => '']],
                'filter.title',
                'The filter.title field must have a value.',
            ],
            'filter:not allowed (singular)' => [
                ['filter' => ['foo' => 'bar', 'title' => 'Hello World']],
                'filter',
                'Filter parameter foo is not allowed.',
            ],
            'filter:not allowed (plural)' => [
                ['filter' => ['foo' => 'bar', 'title' => 'Hello World', 'baz' => 'bat']],
                'filter',
                'Filter parameters foo, baz are not allowed.',
            ],
            'include:not allowed (singular)' => [
                ['include' => 'author,foo'],
                'include',
                'Include path foo is not allowed.',
            ],
            'include:not allowed (plural)' => [
                ['include' => 'author,foo,bar'],
                'include',
                'Include paths foo, bar are not allowed.',
            ],
            'page:invalid' => [
                ['page' => ['number' => 0, 'size' => 10]],
                'page.number',
                'The page.number must be at least 1.',
            ],
            'page:not allowed (singular)' => [
                ['page' => ['foo' => 'bar', 'size' => 10]],
                'page',
                'Page parameter foo is not allowed.',
            ],
            'page:not allowed (plural)' => [
                ['page' => ['foo' => 'bar', 'baz' => 'bat']],
                'page',
                'Page parameters foo, baz are not allowed.',
            ],
            'sort:not allowed (singular)' => [
                ['sort' => 'title,foo'],
                'sort',
                'Sort parameter foo is not allowed.',
            ],
            'sort:not allowed (plural)' => [
                ['sort' => 'title,foo,bar'],
                'sort',
                'Sort parameters foo, bar are not allowed.',
            ],
        ];
    }

    /**
     * @param array $params
     * @param string $param
     * @param string $detail
     * @dataProvider searchProvider
     */
    public function testSearch(array $params, string $param, string $detail)
    {
        $expected = [
            'title' => 'Invalid Query Parameter',
            'status' => "400",
            'detail' => $detail,
            'source' => ['parameter' => $param],
        ];

        $this->resourceType = 'posts';
        $this->doSearch($params)
            ->assertStatus(400)
            ->assertExactJson(['errors' => [$expected]]);
    }

    /**
     * @param array $params
     * @param string $param
     * @param string $detail
     * @dataProvider searchProvider
     */
    public function testSearchRelated(array $params, string $param, string $detail)
    {
        $country = factory(Country::class)->create();

        $this->resourceType = 'countries';
        $this->doReadRelated($country, 'posts', $params)->assertStatus(400)->assertJson(['errors' => [
            [
                'detail' => $detail,
                'source' => ['parameter' => $param],
            ]
        ]]);
    }
}
