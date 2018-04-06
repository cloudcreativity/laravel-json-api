<?php

namespace DummyApp\JsonApi\Comments;

use CloudCreativity\LaravelJsonApi\Contracts\Validators\RelationshipsValidatorInterface;
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
        'page.number' => 'filled|integer|min:1',
        'page.size' => 'filled|integer|between:1,50',
        'filter.created-by' => 'filled|numeric',
    ];

    /**
     * @var array
     */
    protected $allowedSortParameters = [
        'created-at',
        'updated-at',
        'content',
    ];

    /**
     * @var array
     */
    protected $allowedFilteringParameters = [
        'id',
        'created-by',
    ];

    /**
     * @var array
     */
    protected $allowedPagingParameters = [
        'number',
        'size',
    ];

    protected $allowedIncludePaths = ['created-by'];

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
        $relationships->hasOne('commentable', ['posts', 'videos'], is_null($record), true);
    }

}
