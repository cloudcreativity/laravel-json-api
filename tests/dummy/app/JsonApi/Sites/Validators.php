<?php

namespace App\JsonApi\Sites;

use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\LaravelJsonApi\Validators\AbstractValidatorProvider;

class Validators extends AbstractValidatorProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'sites';

    /**
     * @var array
     */
    protected $allowedFilteringParameters = [
        'id',
    ];

    /**
     * @inheritdoc
     */
    protected function attributeRules($record = null)
    {
        $required = $record ? 'sometimes|required' : 'required';

        return [
            'domain' => "$required|url",
            'name' => "$required|string|min:1",
        ];
    }

    /**
     * @inheritdoc
     */
    protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
    {
        // no-op
    }

}

