<?php

namespace DummyApp\JsonApi\Downloads;

use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{

    /**
     * @inheritDoc
     */
    protected function rules($record = null): array
    {
        return [
            'id' => 'nullable|string|min:1',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function queryRules(): array
    {
        return [];
    }

}
