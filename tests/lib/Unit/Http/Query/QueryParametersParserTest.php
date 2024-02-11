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

use CloudCreativity\LaravelJsonApi\Http\Query\SortParameter;
use CloudCreativity\LaravelJsonApi\Http\Query\QueryParametersParser;
use PHPUnit\Framework\TestCase;

class QueryParametersParserTest extends TestCase
{
    public function test(): void
    {
        $parameters = [
            'include' => 'author,comments.user',
            'fields' => ['user' => 'name,email', 'post' => 'title,createdAt'],
            'sort' => '-createdAt,title',
            'page' => ['number' => '2', 'size' => '20'],
            'filter' => ['published' => 'true'],
        ];

        $parser = new QueryParametersParser();
        $actual = $parser->parseQueryParameters($parameters);

        $this->assertSame(['author', 'comments.user'], $actual->getIncludePaths());
        $this->assertSame(['user' => ['name', 'email'], 'post' => ['title', 'createdAt']], $actual->getFieldSets());
        $this->assertEquals([
            new SortParameter('createdAt', false),
            new SortParameter('title', true),
        ], $actual->getSortParameters());
        $this->assertSame($parameters['page'], $actual->getPaginationParameters());
        $this->assertSame($parameters['filter'], $actual->getFilteringParameters());
        $this->assertNull($actual->getUnrecognizedParameters());
    }

    public function testUnrecognizedParameters(): void
    {
        $parameters = [
            'foo' => 'bar',
            'include' => 'author,comments.user',
            'fields' => ['user' => 'name,email', 'post' => 'title,createdAt'],
            'sort' => '-createdAt,title',
            'page' => ['number' => '2', 'size' => '20'],
            'filter' => ['published' => 'true'],
            'baz' => ['bat' => 'foobar'],
        ];

        $parser = new QueryParametersParser();
        $actual = $parser->parseQueryParameters($parameters);

        $this->assertSame([
            'foo' => 'bar',
            'baz' => ['bat' => 'foobar'],
        ], $actual->getUnrecognizedParameters());
    }

    public function testNullable(): void
    {
        $parser = new QueryParametersParser();
        $actual = $parser->parseQueryParameters([
            'sort' => null,
            'include' => null,
            'fields' => null,
        ]);

        $this->assertSame([], $actual->getIncludePaths());
        $this->assertSame([], $actual->getSortParameters());
    }

    public function testEmpty(): void
    {
        $parser = new QueryParametersParser();
        $actual = $parser->parseQueryParameters([]);

        $this->assertNull($actual->getIncludePaths());
        $this->assertNull($actual->getFieldSets());
        $this->assertNull($actual->getSortParameters());
        $this->assertNull($actual->getPaginationParameters());
        $this->assertNull($actual->getFilteringParameters());
        $this->assertNull($actual->getUnrecognizedParameters());
    }
}