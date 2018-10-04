<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use Illuminate\Support\Collection;

/**
 * Class AllowedSortParameters
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class AllowedSortParameters extends AbstractAllowedRule
{

    /**
     * @inheritDoc
     */
    public function message()
    {
        return trans('jsonapi::validation.allowed_sort_parameters');
    }

    /**
     * @inheritDoc
     */
    protected function extract($value): Collection
    {
        $params = is_string($value) ? explode(',', $value) : [];

        return collect($params)->map(function ($param) {
            return ltrim($param, '+-');
        })->unique()->values();
    }

}
