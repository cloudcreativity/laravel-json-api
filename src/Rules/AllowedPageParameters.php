<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use Illuminate\Support\Collection;

/**
 * Class AllowedPageParameters
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class AllowedPageParameters extends AbstractAllowedRule
{

    /**
     * @inheritDoc
     */
    public function message()
    {
        return trans('jsonapi::validation.allowed_page_parameters');
    }

    /**
     * @inheritDoc
     */
    protected function extract($value): Collection
    {
        return collect($value)->keys();
    }

}
