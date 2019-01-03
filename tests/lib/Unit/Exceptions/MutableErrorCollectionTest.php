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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Exceptions;

use CloudCreativity\LaravelJsonApi\Document\Error;
use CloudCreativity\LaravelJsonApi\Exceptions\MutableErrorCollection;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use Neomerx\JsonApi\Document\Error as BaseError;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Class ErrorCollectionTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MutableErrorCollectionTest extends TestCase
{

    public function testIterator()
    {
        $a = new BaseError(null, null, 422);
        $b = new BaseError(null, null, 500);
        $c = new Error(null, null, 400);

        $expected = [Error::cast($a), Error::cast($b), $c];
        $errors = new MutableErrorCollection([$a, $b, $c]);

        $this->assertEquals($expected, $errors->getArrayCopy());
        $this->assertEquals($expected, iterator_to_array($errors));
    }

    public function testMerge()
    {
        $a = new BaseError(null, null, 422);
        $b = new Error(null, null, 400);
        $c = new BaseError(null, null, 500);

        $merge = new ErrorCollection();
        $merge->add($a)->add($b);

        $errors = new MutableErrorCollection([$c]);
        $expected = [Error::cast($c), Error::cast($a), $b];

        $this->assertEquals($errors, $errors->merge($merge));
        $this->assertEquals($expected, $errors->getArrayCopy());
    }

    public function testCastReturnsSame()
    {
        $errors = new MutableErrorCollection();
        $this->assertSame($errors, MutableErrorCollection::cast($errors));
    }

    public function testCastError()
    {
        $error = new BaseError(null, null, 422);
        $expected = new MutableErrorCollection([$error]);
        $this->assertEquals($expected, MutableErrorCollection::cast($error));
    }

    public function testCastBaseCollection()
    {
        $error = new Error(null, null, 422);
        $expected = new MutableErrorCollection([$error]);
        $base = new ErrorCollection();
        $base->add($error);
        $this->assertEquals($expected, MutableErrorCollection::cast($base));
    }

    public function testCastArray()
    {
        $arr = [new Error(null, null, 500)];
        $expected = new MutableErrorCollection($arr);
        $this->assertEquals($expected, MutableErrorCollection::cast($arr));
    }
}
