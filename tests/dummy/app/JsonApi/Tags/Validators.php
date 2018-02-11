<?php

namespace App\JsonApi\Tags;

use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\LaravelJsonApi\Validators\AbstractValidatorProvider;

class Validators extends AbstractValidatorProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'tags';

    /**
     * @inheritDoc
     */
    protected function attributeRules($record = null)
    {
        $required = $record ? 'sometimes|required' : 'required';

        return [
            'name' => "$required|string|between:1,250",
        ];
    }

    /**
     * @inheritDoc
     */
    protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
    {
        $relationships->hasMany('taggables', ['posts', 'videos'], false, true);
    }

}
