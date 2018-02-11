<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Comments;

use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\LaravelJsonApi\Validators\AbstractValidatorProvider;

class Validators extends AbstractValidatorProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'comments';

    /**
     * @var array
     */
    protected $queryRules = [
        'page.number' => 'integer|min:1',
        'page.size' => 'integer|between:1,50',
    ];

    /**
     * @var array
     */
    protected $allowedSortParameters = [
        'created-at',
        'updated-at',
    ];

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
        $required = is_null($record) ? 'required' : 'filled';

        return [
            'content' => "$required|string|min:1",
        ];
    }

    /**
     * @inheritdoc
     */
    protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
    {
        $relationships->hasOne('created-by', 'users', is_null($record), false);
        $relationships->hasOne('commentable', ['posts', 'videos'], is_null($record), true);
    }

}
