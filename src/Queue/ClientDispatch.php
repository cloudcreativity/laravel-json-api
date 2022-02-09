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

use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use DateTimeInterface;
use Illuminate\Foundation\Bus\PendingDispatch;

class ClientDispatch extends PendingDispatch
{

    /**
     * @var string
     */
    protected $api;

    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @var string|bool|null
     */
    protected $resourceId;

    /**
     * ClientDispatch constructor.
     *
     * @param mixed $job
     */
    public function __construct($job)
    {
        parent::__construct($job);
        $this->resourceId = false;
    }

    /**
     * @return string
     */
    public function getApi(): string
    {
        if (is_string($this->api)) {
            return $this->api;
        }

        return $this->api = json_api()->getName();
    }

    /**
     * Set the API that the job belongs to.
     *
     * @param string $api
     * @return ClientDispatch
     */
    public function setApi(string $api): ClientDispatch
    {
        $this->api = $api;

        return $this;
    }

    /**
     * Set the resource type and id that will be created/updated by the job.
     *
     * @param string $type
     * @param string|null $id
     * @return ClientDispatch
     */
    public function setResource(string $type, string $id = null): ClientDispatch
    {
        $this->resourceType = $type;
        $this->resourceId = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getResourceType(): string
    {
        if (is_string($this->resourceType)) {
            return $this->resourceType;
        }

        return $this->resourceType = request()->route(ResourceRegistrar::PARAM_RESOURCE_TYPE);
    }

    /**
     * @return string|null
     */
    public function getResourceId(): ?string
    {
        if (false !== $this->resourceId) {
            return $this->resourceId;
        }

        $request = request();
        $id = $request->route(ResourceRegistrar::PARAM_RESOURCE_ID);

        /** If the binding has been substituted, we need to re-lookup the resource id. */
        if (is_object($id)) {
            $id = json_api()->getContainer()->getSchema($id)->getId($id);
        }

        return $this->resourceId = $id ?: $request->json('data.id');
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getTimeoutAt(): ?DateTimeInterface
    {
        if (method_exists($this->job, 'retryUntil')) {
            return $this->job->retryUntil();
        }

        return $this->job->retryUntil ?? null;
    }

    /**
     * @return int|null
     */
    public function getTimeout(): ?int
    {
        return $this->job->timeout ?? null;
    }

    /**
     * @return int|null
     */
    public function getMaxTries(): ?int
    {
        return $this->job->tries ?? null;
    }

    /**
     * @return AsynchronousProcess
     */
    public function dispatch(): AsynchronousProcess
    {
        $fqn = json_api($this->getApi())
            ->getJobs()
            ->getModel();

        $this->job->clientJob = new $fqn;
        $this->job->clientJob->dispatching($this);

        parent::__destruct();

        return $this->job->clientJob;
    }

    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        // no-op
    }
}
