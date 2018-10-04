<?php

namespace DummyApp\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;
use DummyApp\Post;

class Validators extends AbstractValidators
{

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
     * @var array
     */
    protected $allowedFieldSets = [
        'posts' => ['title', 'content', 'slug', 'author', 'tags'],
    ];

    /**
     * @var array
     */
    protected $allowedPagingParameters = [
        'number',
        'size',
        // these are used as custom keys in a test...
        'page',
        'limit',
    ];

    /**
     * @param Post|null $record
     * @return array|mixed
     */
    protected function rules($record = null): array
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

    /**
     * @inheritDoc
     */
    protected function queryRules(): array
    {
        return [
            'filter.title' => 'filled|string',
            'filter.slug' => 'filled|alpha_dash',
            'filter.published' => 'boolean',
            'page.number' => 'integer|min:1',
            'page.size' => 'integer|between:1,50',
        ];
    }

}
