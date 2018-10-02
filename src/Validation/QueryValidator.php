<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Utils\ErrorBag;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error;

class QueryValidator extends Validator
{

    /**
     * @var Error
     */
    private $prototype;

    /**
     * QueryValidator constructor.
     *
     * @param ValidatorContract $validator
     * @param ErrorInterface|null $prototype
     */
    public function __construct(ValidatorContract $validator, ErrorInterface $prototype = null)
    {
        parent::__construct($validator);
        $this->prototype = $prototype ?: new Error(null, null, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @return ErrorBag
     */
    public function getErrorBag()
    {
        return ErrorBag::create($this->getMessageBag())
            ->withParameters();
    }
}
