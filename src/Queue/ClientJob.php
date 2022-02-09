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

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class ClientJob extends Model implements AsynchronousProcess
{

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $table = 'json_api_client_jobs';

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * Mass-assignable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'api',
        'attempts',
        'completed_at',
        'failed',
        'resource_type',
        'resource_id',
        'timeout',
        'timeout_at',
        'tries',
    ];

    /**
     * Default attributes.
     *
     * @var array
     */
    protected $attributes = [
        'failed' => false,
        'attempts' => 0,
    ];

    /**
     * @var array
     */
    protected $casts = [
        'attempts' => 'integer',
        'failed' => 'boolean',
        'timeout' => 'integer',
        'tries' => 'integer',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'completed_at',
        'timeout_at',
    ];

    /**
     * @inheritdoc
     */
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ClientJobScope());

        static::creating(function (ClientJob $job) {
            $job->uuid = $job->uuid ?: Uuid::uuid4()->toString();
        });
    }

    /**
     * @inheritDoc
     */
    public function getResourceType(): string
    {
        if (!$type = $this->resource_type) {
            throw new RuntimeException('No resource type set.');
        }

        return $type;
    }

    /**
     * @inheritDoc
     */
    public function getLocation(): ?string
    {
        if ($this->failed) {
            return null;
        }

        $type = $this->resource_type;
        $id = $this->resource_id;

        if (!$type || !$id) {
            return null;
        }

        return $this->getApi()->url()->read($type, $id);
    }

    /**
     * @inheritDoc
     */
    public function isPending(): bool
    {
        return !$this->offsetExists('completed_at');
    }

    /**
     * @inheritDoc
     */
    public function dispatching(ClientDispatch $dispatch): void
    {
        $this->fill([
            'api' => $dispatch->getApi(),
            'resource_type' => $dispatch->getResourceType(),
            'resource_id' => $dispatch->getResourceId(),
            'timeout' => $dispatch->getTimeout(),
            'timeout_at' => $dispatch->getTimeoutAt(),
            'tries' => $dispatch->getMaxTries(),
        ])->save();
    }

    /**
     * @inheritDoc
     */
    public function processed($job): void
    {
        $this->update([
            'attempts' => $job->attempts(),
            'completed_at' => $job->isDeleted() ? Carbon::now() : null,
            'failed' => $job->hasFailed(),
        ]);
    }

    /**
     * @param bool $success
     * @return void
     */
    public function completed(bool $success = true): void
    {
        $this->update([
            'attempts' => $this->attempts + 1,
            'completed_at' => Carbon::now(),
            'failed' => !$success,
        ]);
    }

    /**
     * @return Api
     */
    public function getApi(): Api
    {
        if (!$api = $this->api) {
            throw new RuntimeException('Expecting API to be set on client job.');
        }

        return json_api($api);
    }

    /**
     * Set the resource that the client job relates to.
     *
     * @param mixed $resource
     * @return ClientJob
     */
    public function setResource($resource): ClientJob
    {
        $schema = $this->getApi()->getContainer()->getSchema($resource);

        $this->fill([
            'resource_type' => $schema->getResourceType(),
            'resource_id' => $schema->getId($resource),
        ]);

        return $this;
    }

    /**
     * Get the resource that the process relates to.
     *
     * @return mixed|null
     */
    public function getResource()
    {
        if (!$this->resource_type || !$this->resource_id) {
            return null;
        }

        return $this->getApi()->getStore()->find(
            $this->resource_type,
            (string) $this->resource_id
        );
    }

}
