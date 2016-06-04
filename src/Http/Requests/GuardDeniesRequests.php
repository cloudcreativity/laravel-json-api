<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class GuardDeniesRequests
 * @package CloudCreativity\LaravelJsonApi
 */
trait GuardDeniesRequests
{

    /**
     * Returns the
     *
     * @return HttpException
     */
    protected function denied()
    {
        /** @var Guard $auth */
        $auth = app(Guard::class);

        return $auth->check() ?
            new HttpException(Response::HTTP_FORBIDDEN) :
            new HttpException(Response::HTTP_UNAUTHORIZED);
    }
}
