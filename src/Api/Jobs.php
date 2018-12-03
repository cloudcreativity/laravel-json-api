<?php

namespace CloudCreativity\LaravelJsonApi\Api;

use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;

class Jobs
{

    /**
     * @var string
     */
    private $resource;

    /**
     * @var string
     */
    private $model;

    /**
     * @param array $input
     * @return Jobs
     */
    public static function fromArray(array $input): self
    {
        return new self(
            $input['resource'] ?? ResourceRegistrar::KEYWORD_PROCESSES,
            $input['model'] ?? ClientJob::class
        );
    }

    /**
     * Jobs constructor.
     *
     * @param string $resource
     * @param string $model
     */
    public function __construct(string $resource, string $model)
    {
        if (!class_exists($model)) {
            throw new \InvalidArgumentException("Expecting {$model} to be a valid class name.");
        }

        $this->resource = $resource;
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

}
