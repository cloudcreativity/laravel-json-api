<?php

namespace CloudCreativity\LaravelJsonApi\Contracts\Pagination;

use CloudCreativity\JsonApi\Contracts\Pagination\PageInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

interface PagingStrategyInterface
{

    /**
     * @param mixed $query
     * @param EncodingParametersInterface $pagingParameters
     * @return PageInterface
     */
    public function paginate($query, EncodingParametersInterface $pagingParameters);

}
