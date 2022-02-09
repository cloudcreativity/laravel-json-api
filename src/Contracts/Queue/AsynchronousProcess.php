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

namespace CloudCreativity\LaravelJsonApi\Contracts\Queue;

use CloudCreativity\LaravelJsonApi\Queue\ClientDispatch;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;

/**
 * Interface AsynchronousProcess
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface AsynchronousProcess
{

    /**
     * Get the resource type that the process relates to.
     *
     * @return string
     */
    public function getResourceType(): string;

    /**
     * Get the location of the resource that the process relates to, if known.
     *
     * @return string|null
     */
    public function getLocation(): ?string;

    /**
     * Is the process still pending?
     *
     * @return bool
     */
    public function isPending(): bool;

    /**
     * Mark the process as being dispatched.
     *
     * @param ClientDispatch $dispatch
     * @return void
     */
    public function dispatching(ClientDispatch $dispatch): void;

    /**
     * Mark the process as processed.
     *
     * @param JobContract|Job $job
     * @return void
     */
    public function processed($job): void;

}
