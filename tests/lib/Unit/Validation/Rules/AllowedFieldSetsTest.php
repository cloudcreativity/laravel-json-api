<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validation\Rules;

use CloudCreativity\LaravelJsonApi\Rules\AllowedFieldSets;
use PHPUnit\Framework\TestCase;

class AllowedFieldSetsTest extends TestCase
{

    /**
     * @return array
     */
    public function allowedProvider(): array
    {
        return [
            'valid' => [
                ['posts' => 'title,author', 'users' => 'name', 'tags' => 'title'],
                true,
            ],
            'invalid resource type ' => [
                ['posts' => 'title', 'comments' => 'user'],
                false,
            ],
            'invalid resource field' => [
                ['posts' => 'title,foo,content'],
                false,
            ],
        ];
    }

    /**
     * @param array $fields
     * @param bool $expected
     * @dataProvider allowedProvider
     */
    public function test(array $fields, bool $expected)
    {
        $rule = new AllowedFieldSets([
            'posts' => ['title', 'content', 'author'],
            'users' => 'name',
            'tags' => null, // any allowed
            'countries' => [], // none allowed
        ]);

        $this->assertSame($expected, $rule->passes('fields', $fields));
    }

    /**
     * @param array $fields
     * @param bool $expected
     * @dataProvider allowedProvider
     */
    public function testWithMethods(array $fields, bool $expected)
    {
        $rule = (new AllowedFieldSets())
            ->allow('posts', ['title', 'content', 'author'])
            ->allow('users', ['name'])
            ->any('tags')
            ->none('countries');

        $this->assertSame($expected, $rule->passes('fields', $fields));
    }

    public function testAny()
    {
        $rule = new AllowedFieldSets(null);

        $this->assertTrue($rule->passes('fields', [
            'posts' => 'title,content,author',
            'users' => 'name,email',
        ]));
    }

}
