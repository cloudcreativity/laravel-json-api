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

namespace CloudCreativity\LaravelJsonApi\Http\Requests\Concerns;

use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Routing\Route;

trait ProcessRequest
{

    /**
     * @return Route
     */
    abstract protected function getRoute(): Route;

    /**
     * Get the resource id that the request is for.
     *
     * @return string
     */
    public function getProcessId(): string
    {
        return $this->getRoute()->getProcessId();
    }

    /**
     * Get the domain record that the request relates to.
     *
     * @return AsynchronousProcess
     */
    public function getProcess(): AsynchronousProcess
    {
        if (!$process = $this->getRoute()->getProcess()) {
            throw new RuntimeException('Expecting process binding to be substituted.');
        }

        return $process;
    }
}
