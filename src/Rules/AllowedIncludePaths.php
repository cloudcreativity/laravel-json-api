<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use Illuminate\Support\Collection;

/**
 * Class AllowedIncludePaths
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class AllowedIncludePaths extends AbstractAllowedRule
{

    /**
     * @inheritDoc
     */
    public function message()
    {
        return trans('jsonapi::validation.allowed_include_paths');
    }

    /**
     * @inheritDoc
     */
    protected function extract($value): Collection
    {
        $paths = is_string($value) ? explode(',', $value) : [];

        return collect($paths);
    }

}
