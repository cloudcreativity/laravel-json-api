<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
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
     * Get the resource that will be modified as a result of the process.
     *
     * @return mixed|null
     */
    public function getTarget()
    {
        if (!$this->api || !$this->resource_type || !$this->resource_id) {
            return null;
        }

        return json_api($this->api)->getStore()->find(
            ResourceIdentifier::create($this->resource_type, $this->resource_id)
        );
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
     * @inheritdoc
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function (ClientJob $job) {
            $job->uuid = $job->uuid ?: Uuid::uuid4()->toString();
        });
    }

}
