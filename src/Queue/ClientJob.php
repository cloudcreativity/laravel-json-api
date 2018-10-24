<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
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
        'status',
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
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

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
    public function getLocation(): ?string
    {
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
            ResourceIdentifier::create($this->resource_type, $this->resource_id)
        );
    }

}
