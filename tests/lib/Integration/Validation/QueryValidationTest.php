<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Validation;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;

class QueryValidationTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @return array
     */
    public function searchProvider()
    {
        return [
            'fields:not allowed' => [
                ['fields' => ['posts' => 'title,foo']],
                'fields',
                'Sparse field sets must contain only allowed ones.',
            ],
            'filter:invalid' => [
                ['filter' => ['title' => '']],
                'filter.title',
                'The filter.title field must have a value.',
            ],
            'filter:not allowed' => [
                ['filter' => ['foo' => 'bar']],
                'filter',
                'Filter parameters must contain only allowed ones.',
            ],
            'include:not allowed' => [
                ['include' => 'foo'],
                'include',
                'Include paths must contain only allowed ones.',
            ],
            'page:invalid' => [
                ['page' => ['number' => 0, 'size' => 10]],
                'page.number',
                'The page.number must be at least 1.',
            ],
            'page:not allowed' => [
                ['page' => ['foo' => 'bar']],
                'page',
                'Page parameters must contain only allowed ones.',
            ],
            'sort:not allowed' => [
                ['sort' => 'title,foo'],
                'sort',
                'Sort parameters must contain only allowed ones.',
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

        $this->doSearch($params)
            ->assertStatus(400)
            ->assertExactJson(['errors' => [$expected]]);
    }
}
