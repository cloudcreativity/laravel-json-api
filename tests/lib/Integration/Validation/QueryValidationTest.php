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
            'filter:invalid' => [
                ['filter' => ['title' => '']],
                [
                    'title' => 'Bad Request',
                    'status' => '400',
                    'detail' => 'The filter.title field must have a value.',
                    'source' => ['parameter' => "filter.title"],
                ],
            ],
            'page:invalid' => [
                ['page' => ['number' => 0, 'size' => 10]],
                [
                    'title' => 'Bad Request',
                    'status' => '400',
                    'detail' => 'The page.number must be at least 1.',
                    'source' => ['parameter' => 'page.number'],
                ],
            ],
            'include:not allowed' => [
                ['include' => 'foo'],
                [
                    'title' => 'Bad Request',
                    'status' => '400',
                    'detail' => 'Include paths must contain only allowed ones.',
                    'source' => ['parameter' => 'include'],
                ],
            ],
            'sort:not allowed' => [
                ['sort' => 'title,foo'],
                [
                    'title' => 'Bad Request',
                    'status' => '400',
                    'detail' => 'Sort parameters must contain only allowed ones.',
                    'source' => ['parameter' => 'sort'],
                ],
            ],
        ];
    }

    /**
     * @param array $params
     * @param array $error
     * @dataProvider searchProvider
     */
    public function testSearch(array $params, array $error)
    {
        $this->doSearch($params)
            ->assertStatus((int) $error['status'])
            ->assertExactJson(['errors' => [$error]]);
    }
}
