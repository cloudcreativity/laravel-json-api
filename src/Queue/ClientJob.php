<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

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
        'status' => 'queued',
        'failed' => false,
        'attempts' => 0,
    ];

    /**
     * @var array
     */
    protected $casts = [
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
     * @param $job
     * @return $this
     */
    public function fillJob($job)
    {
        return $this->fill([
            'timeout' => isset($job->timeout) ? $job->timeout : null,
            'timeout_at' => method_exists($job, 'retryUntil') ? $job->retryUntil() : null,
            'tries' => isset($job->tries) ? $job->tries : null,
        ]);
    }

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
