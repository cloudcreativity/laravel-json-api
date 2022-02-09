<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests as IlluminateAuthorizesRequests;
use Illuminate\Support\Facades\Auth;

/**
 * Trait AuthorizesRequests
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait AuthorizesRequests
{

    use IlluminateAuthorizesRequests;

    /**
     * The guards to use to authenticate a user.
     *
     * By default we use the `api` guard. Change this to either different
     * named guards, or an empty array to use the default guard.
     *
     * @var array
     */
    protected $guards = ['api'];

    /**
     * @param $ability
     * @param mixed ...$arguments
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function can($ability, ...$arguments)
    {
        $this->authenticate();
        $this->authorize($ability, $arguments);
    }

    /**
     * Determine if the user is logged in.
     *
     * @return void
     * @throws AuthenticationException
     */
    protected function authenticate()
    {
        if (empty($this->guards) && Auth::check()) {
            return;
        }

        foreach ($this->guards as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);
                return;
            }
        }

        throw new AuthenticationException('Unauthenticated.', $this->guards);
    }
}
