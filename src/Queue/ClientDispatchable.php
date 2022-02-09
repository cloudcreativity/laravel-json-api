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

namespace CloudCreativity\LaravelJsonApi\Queue;

trait ClientDispatchable
{

    /**
     * @var ClientJob|null
     */
    public $clientJob;

    /**
     * Start a client dispatch.
     *
     * @param mixed ...$args
     * @return ClientDispatch
     */
    public static function client(...$args): ClientDispatch
    {
        return new ClientDispatch(
            new static(...$args)
        );
    }

    /**
     * Was the job dispatched by a client?
     *
     * @return bool
     */
    public function wasClientDispatched(): bool
    {
        return !is_null($this->clientJob);
    }

    /**
     * Get the JSON API that the job belongs to.
     *
     * @return string|null
     */
    public function api(): ?string
    {
        return optional($this->clientJob)->api;
    }

    /**
     * Get the JSON API resource type that the job relates to.
     *
     * @return string|null
     */
    public function resourceType(): ?string
    {
        return optional($this->clientJob)->resource_type;
    }

    /**
     * Get the JSON API resource id that the job relates to.
     *
     * @return string|null
     */
    public function resourceId(): ?string
    {
        return optional($this->clientJob)->resource_id;
    }

    /**
     * Set the resource that was created by the job.
     *
     * If a job is creating a new resource, this method can be used to update
     * the client job with the created resource. This method does nothing if the
     * job was not dispatched by a client.
     *
     * @param $resource
     * @return void
     */
    public function didCreate($resource): void
    {
        if ($this->wasClientDispatched()) {
            $this->clientJob->setResource($resource)->save();
        }
    }

    /**
     * Mark the client job as completed.
     *
     * Although our queue listeners handle this for you in most cases, there
     * are some scenarios where it is not possible to do this. E.g. if your
     * job deletes a model that is one of its properties, a `ModelNotFoundException`
     * will be triggered when our listener deserializes the job.
     *
     * Therefore this method is provided so that you can manually mark the
     * client job as completed, if needed.
     *
     * @param bool $success
     * @return void
     */
    public function didComplete(bool $success = true): void
    {
        if ($this->wasClientDispatched()) {
            $this->clientJob->completed($success);
        }
    }
}
