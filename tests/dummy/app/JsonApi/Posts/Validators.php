<?php

namespace DummyApp\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;
use DummyApp\Post;

class Validators extends AbstractValidators
{

    /**
     * @var array
     */
    protected $queryRules = [
        'filter.title' => 'filled|string',
        'filter.slug' => 'filled|alpha_dash',
        'filter.published' => 'boolean',
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
        'published',
    ];

    /**
     * @var array
     */
    protected $allowedIncludePaths = [
        'author',
        'comments',
        'comments.created-by',
        'tags',
    ];

    /**
     * @param Post|null $record
     * @return array|mixed
     */
    protected function rules($record = null)
    {
        $slugUnique = 'unique:posts,slug';

        if ($record) {
            $slugUnique .= ',' . $record->getKey();
        }

        return [
            'title' => "required|string|between:1,255",
            'content' => "required|string|min:1",
            'slug' => "required|alpha_dash|$slugUnique",
            'author.type' => 'in:users',
            'tags.*.type' => 'in:tags',
        ];
    }

}
