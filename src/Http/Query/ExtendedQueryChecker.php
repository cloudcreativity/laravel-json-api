<?php

namespace CloudCreativity\LaravelJsonApi\Http\Query;

use CloudCreativity\JsonApi\Exceptions\ValidationException;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\FilterValidatorInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryCheckerInterface;

class ExtendedQueryChecker implements QueryCheckerInterface
{

    /**
     * @var QueryCheckerInterface
     */
    protected $baseChecker;

    /**
     * @var FilterValidatorInterface
     */
    protected $filterValidator;


    /**
     * ExtendedQueryChecker constructor.
     *
     * @param QueryCheckerInterface $baseChecker
     * @param FilterValidatorInterface $filterValidator
     */
    public function __construct(QueryCheckerInterface $baseChecker, FilterValidatorInterface $filterValidator)
    {
        $this->baseChecker = $baseChecker;
        $this->filterValidator = $filterValidator;
    }

    /**
     * @param EncodingParametersInterface $parameters
     */
    public function checkQuery(EncodingParametersInterface $parameters)
    {
        $this->baseChecker->checkQuery($parameters);
        $this->validateFilters((array) $parameters->getFilteringParameters());
    }

    /**
     * @param array $filters
     * @return void
     */
    protected function validateFilters(array $filters)
    {
        if (!$this->filterValidator->isValid($filters)) {
            throw new ValidationException(
                $this->filterValidator->getErrors(),
                ValidationException::HTTP_CODE_BAD_REQUEST
            );
        }
    }
}
