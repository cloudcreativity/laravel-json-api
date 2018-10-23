<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Foundation\Bus\PendingDispatch;

class ClientDispatch extends PendingDispatch
{

    /**
     * @var ClientJob
     */
    protected $clientJob;

    /**
     * ClientDispatch constructor.
     *
     * @param mixed $job
     */
    public function __construct($job)
    {
        parent::__construct($job);
        $this->clientJob = new ClientJob();
    }

    /**
     * Set the API that the job belongs to.
     *
     * @param string $api
     * @return ClientDispatch
     */
    public function forApi(string $api): ClientDispatch
    {
        $this->clientJob->fill(compact('api'));

        return $this;
    }

    /**
     * Set the resource type and id that will be created/updated by the job.
     *
     * @param string $type
     * @param string|null $id
     * @return ClientDispatch
     */
    public function forResource(string $type, string $id = null): ClientDispatch
    {
        $this->clientJob->fill([
            'resource_type' => $type,
            'resource_id' => $id,
        ]);

        return $this;
    }

    /**
     * @return ClientJob
     */
    public function dispatch(): ClientJob
    {
        if ($this->didDispatch()) {
            throw new RuntimeException('Only expecting to dispatch client job once.');
        }

        $this->clientJob->save();
        $this->job->clientJob = $this->clientJob;

        parent::__destruct();

        return $this->clientJob;
    }

    /**
     * @return bool
     */
    public function didDispatch(): bool
    {
        return $this->clientJob->exists;
    }

    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        // no-op
    }
}
