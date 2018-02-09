<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Posts;

use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use CloudCreativity\LaravelJsonApi\Validators\AbstractValidatorProvider;

class Validators extends AbstractValidatorProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @var array
     */
    protected $queryRules = [
        'filter.title' => 'string|min:1',
        'filter.slug' => 'sometimes|required|alpha_dash',
        'page.number' => 'integer|min:1',
        'page.size' => 'integer|between:1,50',
    ];

    /**
     * @var array
     */
    protected $allowedSortParameters = [
        'created-at',
        'updated-at',
        'title',
        'slug',
    ];

    /**
     * @var array
     */
    protected $allowedFilteringParameters = [
        'id',
        'title',
        'slug',
    ];

    /**
     * @inheritdoc
     */
    protected function attributeRules($record = null)
    {
        /** @var Post $record */

        // The JSON API spec says the client does not have to send all attributes for an update request, so
        // if the record already exists we need to include a 'sometimes' before required.
        $required = $record ? 'sometimes|required' : 'required';
        $slugUnique = 'unique:posts,slug';

        if ($record) {
            $slugUnique .= ',' . $record->getKey();
        }

        return [
            'title' => "$required|string|between:1,255",
            'content' => "$required|string|min:1",
            'slug' => "$required|alpha_dash|$slugUnique",
        ];
    }

    /**
     * @inheritdoc
     */
    protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
    {
        $relationships->hasOne('author', 'users', is_null($record), true);
    }

}
