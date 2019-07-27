<?php

namespace CloudCreativity\LaravelJsonApi\Encoder\Neomerx\Document;

use CloudCreativity\LaravelJsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class Errors implements DocumentInterface
{

    /**
     * @var ErrorInterface[]
     */
    private $errors;

    /**
     * @var int|null
     */
    private $defaultHttpStatus;

    /**
     * Cast a value to an errors document.
     *
     * @param ErrorInterface|iterable|JsonApiException $value
     * @return Errors
     */
    public static function cast($value): self
    {
        $status = null;

        if ($value instanceof JsonApiException) {
            $status = $value->getHttpCode();
            $value = $value->getErrors();
        }

        if ($value instanceof ErrorInterface) {
            $value = [$value];
        }

        if (!is_iterable($value)) {
            throw new \UnexpectedValueException('Invalid Neomerx error collection.');
        }

        $errors = new self(...collect($value)->values());
        $errors->setDefaultStatus($status);

        return $errors;
    }

    /**
     * Errors constructor.
     *
     * @param ErrorInterface ...$errors
     */
    public function __construct(ErrorInterface ...$errors)
    {
        $this->errors = $errors;
    }

    /**
     * Set the default HTTP status.
     *
     * @param int|null $status
     * @return $this
     */
    public function setDefaultStatus(?int $status): self
    {
        $this->defaultHttpStatus = $status;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return json_api()->encoder()->serializeErrors($this->errors);
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return json_api()->response()->errors(
            $this->errors,
            $this->defaultHttpStatus
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }


}
