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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Repositories\ErrorRepository;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase as BaseTestCase;
use CloudCreativity\LaravelJsonApi\Utils\Replacer;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorFactory;
use Illuminate\Contracts\Validation\Factory;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class TestCase
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class TestCase extends BaseTestCase
{

    /**
     * @var ErrorRepository
     */
    protected $errorRepository;

    /**
     * @var ValidatorErrorFactory
     */
    protected $errorFactory;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $store;

    /**
     * @var ValidatorFactory
     */
    protected $validatorFactory;

    /**
     * @var array
     */
    protected $resourceTypes = [];

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $store = $this->createMock(StoreInterface::class);
        $config = require __DIR__ . '/../../../../config/json-api-errors.php';

        $store->method('isType')->willReturnCallback(function ($type) {
            return in_array($type, $this->resourceTypes, true);
        });

        $this->errorRepository = new ErrorRepository(new Replacer());
        $this->errorRepository->configure($config);
        $this->errorFactory = new ValidatorErrorFactory($this->errorRepository);
        $this->validatorFactory = new ValidatorFactory(
            $this->errorFactory,
            $store,
            $this->createMock(Factory::class)
        );
        $this->store = $store;
    }

    /**
     * @param bool $exists
     * @return $this
     */
    protected function willExist($exists = true)
    {
        $this->store->method('exists')->willReturn($exists);
        return $this;
    }

    /**
     * @return $this
     */
    protected function willNotExist()
    {
        return $this->willExist(false);
    }

    /**
     * @param ErrorCollection $errors
     * @param $pointer
     * @param $errorKey
     * @param $status
     */
    protected function assertErrorAt(ErrorCollection $errors, $pointer, $errorKey, $status = null)
    {
        $error = $this->findErrorAt($errors, $pointer);
        $expected = $this->errorRepository->error($errorKey);

        if ($status) {
            $expected->setStatus($status);
        }

        $this->assertEquals($expected->getTitle(), $error->getTitle(), 'Unexpected error title.');
        $this->assertEquals($expected->getStatus(), $error->getStatus(), 'Unexpected error status.');
    }

    /**
     * @param ErrorCollection $errors
     * @param $pointer
     * @param $needle
     */
    protected function assertDetailContains(ErrorCollection $errors, $pointer, $needle)
    {
        $error = $this->findErrorAt($errors, $pointer);

        $this->assertContains($needle, $error->getDetail(), "Invalid detail for error: $pointer");
    }

    /**
     * @param ErrorCollection $errors
     * @param $pointer
     * @param $expected
     */
    protected function assertDetailIs(ErrorCollection $errors, $pointer, $expected)
    {
        $error = $this->findErrorAt($errors, $pointer);

        $this->assertEquals($expected, $error->getDetail(), "Invalid detail for error: $pointer");
    }

    /**
     * @param ErrorCollection $errors
     * @param $pointer
     * @return ErrorInterface
     */
    protected function findErrorAt(ErrorCollection $errors, $pointer)
    {
        /** @var ErrorInterface $error */
        foreach ($errors as $error) {
            $source = (array) $error->getSource();
            $check = isset($source[ErrorInterface::SOURCE_POINTER])
                ? $source[ErrorInterface::SOURCE_POINTER] : null;

            if ($pointer === $check) {
                return $error;
            }
        }

        $pointers = implode(', ', $this->pointers($errors));
        $this->fail("$pointer not in pointers: [$pointers]");

        return null;
    }

    /**
     * @param ErrorCollection $errors
     * @return array
     */
    protected function pointers(ErrorCollection $errors)
    {
        $pointers = [];

        /** @var ErrorInterface $error */
        foreach ($errors as $error) {
            $source = (array) $error->getSource();
            $pointer = isset($source[ErrorInterface::SOURCE_POINTER])
                ? $source[ErrorInterface::SOURCE_POINTER] : null;

            if ($pointer) {
                $pointers[] = $pointer;
            }
        }

        return array_unique($pointers);
    }
}
