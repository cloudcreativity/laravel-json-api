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
    protected function extract($value): Collection
    {
        return collect($value)->keys();
    }

}
