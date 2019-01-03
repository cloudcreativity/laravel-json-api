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
use CloudCreativity\LaravelJsonApi\Exceptions\ValidationException;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;

/**
 * Class ValidationExceptionTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ValidationExceptionTest extends TestCase
{

    public function testDefaultStatus()
    {
        $ex = new ValidationException([]);
        $this->assertSame(ValidationException::HTTP_CODE_BAD_REQUEST, $ex->getHttpCode());
    }

    public function testErrorStatus()
    {
        $err = new Error(null, null, 401);
        $ex = new ValidationException($err);
        $this->assertEquals(401, $ex->getHttpCode());
    }
}
