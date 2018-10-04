<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use Illuminate\Support\Collection;

/**
 * Class AllowedFilterParameters
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class AllowedFilterParameters extends AbstractAllowedRule
{

    /**
     * @inheritDoc
     */
    public function message()
    {
        return trans('jsonapi::validation.allowed_filter_parameters');
    }

    /**
     * @inheritDoc
     */
    protected function extract($value): Collection
    {
        return collect($value)->keys();
    }

}
