<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Rules\AllowedFieldSets;
use CloudCreativity\LaravelJsonApi\Rules\AllowedFilterParameters;
use CloudCreativity\LaravelJsonApi\Rules\AllowedIncludePaths;
use CloudCreativity\LaravelJsonApi\Rules\AllowedPageParameters;
use CloudCreativity\LaravelJsonApi\Rules\AllowedSortParameters;
use CloudCreativity\LaravelJsonApi\Rules\DisallowedParameter;
use DemeterChain\A;
use Illuminate\Support\Collection;

/**
 * Class AbstractValidators
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractValidators implements ValidatorFactoryInterface
{

    /**
     * Custom messages for the resource validator.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Custom attributes for the resource validator.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Validation rules for query parameters.
     *
     * @var array
     */
    protected $queryRules = [];

    /**
     * Custom messages for the query parameters validator.
     *
     * @var array
     */
    protected $queryMessages = [];

    /**
     * Custom attributes for the query parameters validator.
     *
     * @var array
     */
    protected $queryAttributes = [];

    /**
     * What include paths the client is allowed to request.
     *
     * Empty array = clients are not allowed to specify include paths.
     * Null = all paths are allowed.
     *
     * @var string[]|null
     */
    protected $allowedIncludePaths = [];

    /**
     * What sort field names can be sent by the client.
     *
     * Empty array = clients are not allowed to specify sort fields.
     * Null = clients can specify any sort fields.
     *
     * @var string[]|null
     */
    protected $allowedSortParameters = [];

    /**
     * The allowed filtering parameters.
     *
     * By default we set this to `null` to allow any filtering parameters, as we expect
     * the filtering parameters to be validated using the query parameter validator.
     *
     * Empty array = clients are not allowed to request filtering.
     * Null = clients can specify any filtering fields they want.
     *
     * @var string[]|null
     */
    protected $allowedFilteringParameters = null;

    /**
     * The allowed paging parameters.
     *
     * By default we set this to `null` to allow any paging parameters, as we expect
     * the paging parameters to be validated using the query parameter validator.
     *
     * Empty array = clients are not allowed to request paging.
     * Null = clients can specify any paging fields they want.
     *
     * @var string[]|null
     */
    protected $allowedPagingParameters = null;

    /**
     * What field sets the client is allowed to request per JSON API resource object type.
     *
     * Null = the client can specify any fields for any resource object type.
     * Empty array = the client cannot specify any fields for any resource object type (i.e. all denied.)
     * Non-empty array = configuration per JSON API resource object type. The key should be the type, the value should
     * be either null (all fields allowed for that type), empty array (no fields allowed for that type) or an array
     * of string values listing the allowed fields for that type.
     *
     * @return AllowedFieldSets
     */
    protected $allowedFieldSets = null;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Get resource validation rules.
     *
     * @param mixed|null $record
     *      the record being updated, or null if creating a resource.
     * @return mixed
     */
    abstract protected function rules($record = null);

    /**
     * AbstractValidators constructor.
     *
     * @param Factory $factory
     * @param ContainerInterface $container
     */
    public function __construct(Factory $factory, ContainerInterface $container)
    {
        $this->factory = $factory;
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function create(array $document): ValidatorInterface
    {
        return $this->createResourceValidator(
            $this->createData($document),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );
    }

    /**
     * @inheritDoc
     */
    public function update($record, array $document): ValidatorInterface
    {
        return $this->createResourceValidator(
            $this->updateData($record, $document),
            $this->rules($record),
            $this->messages(),
            $this->attributes()
        );
    }

    /**
     * @inheritDoc
     */
    public function modifyRelationship($record, string $field, array $document): ValidatorInterface
    {
        return $this->factory->createResourceValidator(
            $this->relationshipData($record, $field, $document),
            $this->relationshipRules($record, $field),
            $this->messages(),
            $this->attributes()
        );
    }

    /**
     * @inheritDoc
     */
    public function fetchManyQuery(array $params): ValidatorInterface
    {
        return $this->queryValidator($params);
    }

    /**
     * @inheritDoc
     */
    public function fetchQuery(array $params): ValidatorInterface
    {
        /**
         * Allow filter params, but not sort and page, for a resource GET.
         * Do not allow the `id` filter as it does not make sense as the request
         * is already limited by id.
         *
         * @see https://github.com/cloudcreativity/laravel-json-api/issues/218
         */
        return $this->createQueryValidator(
            $params,
            $this->queryRulesExcludingFilterId('sort', 'page'),
            $this->queryMessages(),
            $this->queryAttributes()
        );
    }

    /**
     * @inheritDoc
     */
    public function modifyQuery(array $params): ValidatorInterface
    {
        /** For modify resource requests, do not allow filter, sort and page. */
        return $this->queryValidator($params, ['filter', 'sort', 'page']);
    }

    /**
     * @inheritDoc
     */
    public function fetchRelatedQuery(array $params): ValidatorInterface
    {
        return $this->fetchManyQuery($params);
    }

    /**
     * @inheritDoc
     */
    public function fetchRelationshipQuery(array $params): ValidatorInterface
    {
        return $this->queryValidator($params);
    }

    /**
     * @inheritDoc
     */
    public function modifyRelationshipQuery(array $params): ValidatorInterface
    {
        return $this->fetchRelationshipQuery($params);
    }

    /**
     * Get custom messages for a resource validator.
     *
     * @return array
     */
    protected function messages(): array
    {
        return $this->messages;
    }

    /**
     * Get custom attributes for a resource validator.
     *
     * @return array
     */
    protected function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get validation data for creating a domain record.
     *
     * @param array $document
     * @return array
     */
    protected function createData(array $document): array
    {
        return $document['data'];
    }

    /**
     * Get validation data for updating a domain record.
     *
     * The JSON API spec says:
     *
     * > If a request does not include all of the attributes for a resource,
     * > the server MUST interpret the missing attributes as if they were included
     * > with their current values. The server MUST NOT interpret missing
     * > attributes as null values.
     *
     * So that the validator has access to the current values of attributes, we
     * merge to provided new attributes over the top of the existing attribute
     * values.
     *
     * @param mixed $record
     *      the record being updated.
     * @param array $document
     *      the JSON API document to validate.
     * @return array
     */
    protected function updateData($record, array $document): array
    {
        $resource = $document['data'];

        $resource['attributes'] = $this->extractAttributes(
            $record,
            isset($resource['attributes']) ? $resource['attributes'] : []
        )->all();

        return $resource;
    }

    /**
     * Extract attributes for a resource update.
     *
     * @param $record
     * @param array $new
     * @return Collection
     */
    protected function extractAttributes($record, array $new): Collection
    {
        return $this->currentAttributeValues($record)->merge($new);
    }

    /**
     * Get the current attribute values for the provided record.
     *
     * @param $record
     * @return Collection
     */
    protected function currentAttributeValues($record): Collection
    {
        return collect($this->container->getSchema($record)->getAttributes($record));
    }

    /**
     * Get validation data for modifying a relationship.
     *
     * @param $record
     * @param $field
     * @param array $document
     * @return array
     */
    protected function relationshipData($record, $field, array $document): array
    {
        $schema = $this->container->getSchema($record);

        return [
            'type' => $schema->getResourceType(),
            'id' => $schema->getId($record),
            'relationships' => [
                $field => [
                    'data' => $document['data'],
                ],
            ],
        ];
    }

    /**
     * Get validation rules for a specified relationship field.
     *
     * @param $record
     * @param $field
     * @return array
     */
    protected function relationshipRules($record, $field): array
    {
        return collect($this->rules($record))->filter(function ($v, $key) use ($field) {
            return starts_with($key, $field);
        })->all();
    }

    /**
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return ValidatorInterface
     */
    protected function createResourceValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): ValidatorInterface {
        return $this->factory->createResourceValidator(
            $data,
            $rules,
            $messages,
            $customAttributes
        );
    }

    /**
     * @return array
     */
    protected function queryRules(): array
    {
        return collect($this->defaultQueryRules())
            ->merge($this->queryRules)
            ->all();
    }

    /**
     * @return array
     */
    protected function defaultQueryRules(): array
    {
        return [
            'fields' => [
                'bail',
                'array',
                $this->allowedFieldSets(),
            ],
            'fields.*' => [
                'filled',
                'string',
            ],
            'filter' => [
                'bail',
                'array',
                $this->allowedFilteringParameters(),
            ],
            'include' => [
                'bail',
                'string',
                $this->allowedIncludePaths(),
            ],
            'page' => [
                'bail',
                'array',
                $this->allowedSortParameters(),
            ],
            'sort' => [
                'bail',
                'string',
                $this->allowedSortParameters(),
            ],
        ];
    }

    /**
     * Get rules to disallow the provided keys.
     *
     * @param string ...$keys
     * @return Collection
     */
    protected function excluded(string ...$keys): Collection
    {
        return collect($keys)->mapWithKeys(function ($key) {
            return [$key => new DisallowedParameter($key)];
        });
    }

    /**
     * @param string ...$keys
     * @return array
     */
    protected function queryRulesWithout(string ...$keys): array
    {
        if (empty($keys)) {
            return $this->queryRules();
        }

        return collect($this->queryRules())->reject(function ($value, $key) use ($keys) {
            return starts_with($key, $keys);
        })->merge($this->excluded(...$keys))->all();
    }

    /**
     * @param string ...$without
     * @return array
     */
    protected function queryRulesExcludingFilterId(string ...$without): array
    {
        $without[] = 'filter.id';
        $rules = $this->queryRulesWithout(...$without);

        $rules['filter'] = [
            'bail',
            'array',
            $this->allowedFilteringParameters()->forget('id')
        ];

        return $rules;
    }

    /**
     * @return array
     */
    protected function queryMessages(): array
    {
        return $this->queryMessages;
    }

    /**
     * @return array
     */
    protected function queryAttributes(): array
    {
        return $this->queryAttributes;
    }

    /**
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return ValidatorInterface
     */
    protected function createQueryValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): ValidatorInterface {
        return $this->factory->createQueryValidator($data, $rules, $messages, $customAttributes);
    }

    /**
     * Get a validator for all query parameters.
     *
     * @param array $params
     * @param array|string|null $without
     * @return ValidatorInterface
     */
    protected function queryValidator(array $params, $without = null): ValidatorInterface
    {
        $without = (array) $without;

        return $this->createQueryValidator(
            $params,
            $this->queryRulesWithout(...$without),
            $this->queryMessages(),
            $this->queryAttributes()
        );
    }

    /**
     * Get a rule for the allowed include paths.
     *
     * @return AllowedIncludePaths
     */
    protected function allowedIncludePaths(): AllowedIncludePaths
    {
        return new AllowedIncludePaths($this->allowedIncludePaths);
    }

    /**
     * Get a rule for the allowed field sets.
     *
     * @return AllowedFieldSets
     */
    protected function allowedFieldSets(): AllowedFieldSets
    {
        return new AllowedFieldSets($this->allowedFieldSets);
    }

    /**
     * Get a rule for the allowed sort parameters.
     *
     * @return AllowedSortParameters
     */
    protected function allowedSortParameters(): AllowedSortParameters
    {
        return new AllowedSortParameters($this->allowedSortParameters);
    }

    /**
     * Get a rule for the allowed page parameters.
     *
     * @return AllowedPageParameters
     */
    protected function allowedPagingParameters(): AllowedPageParameters
    {
        return new AllowedPageParameters($this->allowedPagingParameters);
    }

    /**
     * Get a rule for the allowed filtering parameters.
     *
     * @return AllowedFilterParameters
     */
    protected function allowedFilteringParameters(): AllowedFilterParameters
    {
        return new AllowedFilterParameters($this->allowedFilteringParameters);
    }

}
