<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
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
     * @var array
     */
    protected $fillable = [
        'api',
        'resource_type',
        'resource_id',
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
        static::creating(function (ClientJob $job) {
            $job->uuid = $job->uuid ?: Uuid::uuid4()->toString();
        });
    }

}
