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
    protected function extract($value): Collection
    {
        return collect($value)->keys();
    }

}
