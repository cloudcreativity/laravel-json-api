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
     * @param string $resourceType
     * @param mixed $job
     */
    public function __construct(string $resourceType, $job)
    {
        parent::__construct($job);
        $this->clientJob = new ClientJob(['resource_type' => $resourceType]);
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
