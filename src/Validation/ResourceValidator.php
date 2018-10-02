<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Utils\ErrorBag;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class ResourceValidator extends Validator
{

    /**
     * @var ResourceObject
     */
    protected $resource;

    /**
     * @var ErrorInterface
     */
    protected $prototype;

    /**
     * ResourceValidator constructor.
     *
     * @param ValidatorContract $validator
     * @param ResourceObject $resource
     * @param ErrorInterface|null $prototype
     */
    public function __construct(
        ValidatorContract $validator,
        ResourceObject $resource,
        ErrorInterface $prototype = null
    ) {
        parent::__construct($validator);
        $this->resource = $resource;
        $this->prototype = $prototype ?: new Error(null, null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @inheritDoc
     */
    public function getErrorBag()
    {
        return ErrorBag::create($this->getMessageBag(), $this->prototype)
            ->withKeyMap($this->resource);
    }

}
