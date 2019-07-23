<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use Illuminate\Support\Arr;

class HasMany extends HasOne
{

    /**
     * @inheritDoc
     */
    protected function accept(?array $data): bool
    {
        if (is_null($data)) {
            return false;
        }

        if (empty($data) && $this->acceptsNone()) {
            return true;
        } else if (empty($data)) {
            return false;
        }

        if (Arr::isAssoc($data)) {
            return false;
        }

        return collect($data)->every(function ($value) {
            return $this->acceptType($value);
        });
    }
}
