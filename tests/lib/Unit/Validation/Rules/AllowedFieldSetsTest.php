<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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
