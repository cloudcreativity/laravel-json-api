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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit;

use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Contracts\Container\Container;

class ServiceTest extends TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Container
     */
    private $container;

    /**
     * @var JsonApiService
     */
    private $service;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->container = $this->createMock(Container::class);
        $this->service = new JsonApiService($this->container);
    }

    public function testDefault()
    {
        $this->assertSame('default', $this->service->defaultApi());
        $this->service->defaultApi('foo');
        $this->assertSame('foo', $this->service->defaultApi());
    }
}
