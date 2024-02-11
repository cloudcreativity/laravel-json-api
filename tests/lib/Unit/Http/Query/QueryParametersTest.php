<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Http\Query;

use CloudCreativity\LaravelJsonApi\Http\Query\QueryParameters;
use CloudCreativity\LaravelJsonApi\Http\Query\SortParameter;
use PHPUnit\Framework\TestCase;

class QueryParametersTest extends TestCase
{
    public function test(): void
    {
        $params = new QueryParameters(
            $include = ['author', 'comments.user'],
            $fields = ['posts' => ['author', 'createdAt', 'comments'], 'users' => ['name']],
            $sort = [new SortParameter('createdAt', false), new SortParameter('title', true)],
            $page = ['number' => 1, 'size' => 25],
            $filter = ['published' => 'true'],
            $unrecognised = ['foo' => 'bar'],
        );

        $this->assertSame($include, $params->getIncludePaths());
        $this->assertSame($fields, $params->getFieldSets());
        $this->assertSame($fields['users'], $params->getFieldSet('users'));
        $this->assertSame($sort, $params->getSortParameters());
        $this->assertSame($page, $params->getPaginationParameters());
        $this->assertSame($filter, $params->getFilteringParameters());
        $this->assertSame($unrecognised, $params->getUnrecognizedParameters());
        $this->assertFalse($params->isEmpty());

        $this->assertSame($include = 'author,comments.user', $params->getIncludeParameter());
        $this->assertSame(
            $fields = ['posts' => 'author,createdAt,comments', 'users' => 'name'],
            $params->getFieldsParameter()
        );
        $this->assertSame($sort = '-createdAt,title', $params->getSortParameter());
        $this->assertEquals($all = [
            'foo' => 'bar',
            'include' => $include,
            'fields' => $fields,
            'sort' => $sort,
            'page' => $page,
            'filter' => $filter,
        ], $params->all());
        $this->assertEquals($all, $params->toArray());
    }

    public function testEmpty(): void
    {
        $params = new QueryParameters();

        $this->assertNull($params->getIncludePaths());
        $this->assertNull($params->getFieldSets());
        $this->assertNull($params->getFieldSet('posts'));
        $this->assertNull($params->getSortParameters());
        $this->assertNull($params->getPaginationParameters());
        $this->assertNull($params->getFilteringParameters());
        $this->assertNull($params->getUnrecognizedParameters());
        $this->assertTrue($params->isEmpty());
        $this->assertNull($params->getIncludeParameter());
        $this->assertEmpty($params->getFieldsParameter());
        $this->assertNull($params->getSortParameter());
        $this->assertEquals([
            'include' => null,
            'fields' => null,
            'sort' => null,
            'page' => null,
            'filter' => null,
        ], $params->all());
        $this->assertEmpty($params->toArray());
    }

    public function testEmptyWithEmptyArrayValues(): void
    {
        $params = new QueryParameters(
            [],
            [],
            [],
            [],
            [],
            [],
        );

        $this->assertSame([], $params->getIncludePaths());
        $this->assertSame([], $params->getFieldSets());
        $this->assertNull($params->getFieldSet('posts'));
        $this->assertSame([], $params->getSortParameters());
        $this->assertSame([], $params->getPaginationParameters());
        $this->assertSame([], $params->getFilteringParameters());
        $this->assertSame([], $params->getUnrecognizedParameters());
        $this->assertTrue($params->isEmpty());
        $this->assertNull($params->getIncludeParameter());
        $this->assertEmpty($params->getFieldsParameter());
        $this->assertNull($params->getSortParameter());
        $this->assertEquals([
            'include' => null,
            'fields' => null,
            'sort' => null,
            'page' => [],
            'filter' => [],
        ], $params->all());
        $this->assertEmpty($params->toArray());
    }
}