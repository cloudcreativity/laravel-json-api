<?php

namespace CloudCreativity\LaravelJsonApi\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResourceNotFoundException extends NotFoundHttpException
{

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $id;

    /**
     * ResourceNotFoundException constructor.
     *
     * @param string $type
     * @param string $id
     * @param \Exception|null $previous
     * @param int $code
     * @param array $headers
     */
    public function __construct(
        string $type,
        string $id,
        \Exception $previous = null,
        int $code = 0,
        array $headers = []
    ) {
        parent::__construct("Resource {$type} with id {$id} does not exist.", $previous, $code, $headers);
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getResourceId(): string
    {
        return $this->id;
    }
}
