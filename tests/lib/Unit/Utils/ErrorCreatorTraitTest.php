<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Utils;

use CloudCreativity\LaravelJsonApi\Contracts\Document\MutableErrorInterface;
use CloudCreativity\LaravelJsonApi\Document\Error;
use CloudCreativity\LaravelJsonApi\Exceptions\MutableErrorCollection;
use CloudCreativity\LaravelJsonApi\Repositories\ErrorRepository;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use CloudCreativity\LaravelJsonApi\Utils\ErrorCreatorTrait;
use CloudCreativity\LaravelJsonApi\Utils\Replacer;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

/**
 * Class ErrorCreatorTraitTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ErrorCreatorTraitTest extends TestCase
{

    use ErrorCreatorTrait;

    /**
     * @var ErrorRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->repository = new ErrorRepository(new Replacer());
    }

    public function testAddErrorObject()
    {
        $expected = new Error('123');

        $this->assertSame($expected, $this->addError($expected));
        $this->assertError($expected);
    }

    public function testAddErrorKey()
    {
        $expected = new Error('123');
        $expected->setDetail('Expecting to see bar as the value');

        $this->willSee('my-error', '123', 'Expecting to see {foo} as the value');
        $this->assertEquals($expected, $this->addError('my-error', ['foo' => 'bar']));
        $this->assertError($expected);
    }

    public function testAddErrorWithPointer()
    {
        $expected = new Error('123');
        $expected->setDetail('Expecting to see bar as the value');
        $expected->setSourcePointer($pointer = '/foo/bar');

        $this->willSee('my-error', '123', 'Expecting to see {foo} as the value');
        $this->assertEquals($expected, $this->addErrorWithPointer('my-error', $pointer, ['foo' => 'bar']));
        $this->assertError($expected);
    }

    public function testAddErrorWithParameter()
    {
        $expected = new Error('123');
        $expected->setDetail('Expecting to see bar as the value');
        $expected->setSourceParameter($param = 'foobar');

        $this->willSee('my-error', '123', 'Expecting to see {foo} as the value');
        $this->assertEquals($expected, $this->addErrorWithParameter('my-error', $param, ['foo' => 'bar']));
        $this->assertError($expected);
    }

    /**
     * @param $key
     * @param $id
     * @param $detail
     * @return $this
     */
    private function willSee($key, $id, $detail = null)
    {
        $this->repository->configure([
            $key => [
                MutableErrorInterface::ID => $id,
                MutableErrorInterface::DETAIL => $detail,
            ],
        ]);

        return $this;
    }

    /**
     * @param ErrorInterface $expected
     * @return $this
     */
    private function assertError(ErrorInterface $expected)
    {
        $expected = new MutableErrorCollection([$expected]);
        $actual = $this->getErrors();

        $this->assertEquals($expected, $actual);

        return $this;
    }

    /**
     * @return ErrorRepository
     */
    protected function getErrorRepository()
    {
        return $this->repository;
    }
}
