<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Codec\ChecksMediaTypes;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Rules\AllowedFieldSets;
use CloudCreativity\LaravelJsonApi\Rules\AllowedFilterParameters;
use CloudCreativity\LaravelJsonApi\Rules\AllowedIncludePaths;
use CloudCreativity\LaravelJsonApi\Rules\AllowedPageParameters;
use CloudCreativity\LaravelJsonApi\Rules\AllowedSortParameters;
use CloudCreativity\LaravelJsonApi\Rules\DisallowedParameter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function json_decode;
use function json_encode;

/**
 * Class AbstractValidators
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractValidators implements ValidatorFactoryInterface
{

    use ChecksMediaTypes;

    /**
     * Whether the resource supports client-generated ids.
     *
     * A boolean to indicate whether client-generated ids are accepted. If
     * null, this will be worked out based on whether there are any validation
     * rules for the resource's `id` member.
     *
     * @var bool|null
     */
    protected $clientIds;

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
     * Custom attributes for the delete resource validator.
     *
     * @var array
     */
    protected $deleteMessages = [];

    /**
     * Custom attributes for the delete resource validator.
     *
     * @var array
     */
    protected $deleteAttributes = [];

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
     * The include paths a client is allowed to request.
     *
     * @var string[]|null
     *      the allowed paths, an empty array for none allowed, or null to allow all paths.
     */
    protected $allowedIncludePaths = [];

    /**
     * The sort field names a client is allowed send.
     *
     * @var string[]|null
     *      the allowed fields, an empty array for none allowed, or null to allow all fields.
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
     * @todo 3.0.0 make this `[]` by default, as we now loop through filter parameters.
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
     * Whether existing resource attributes should be validated on for an update.
     *
     * If this is set to false, the validator instance will not be provided with the
     * resource's existing attribute values when validating an update (PATCH) request.
     *
     * @var bool
     */
    protected $validateExisting = true;

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
     * @param array $data
     *      the data that is being validated.
     * @return mixed
     */
    abstract protected function rules($record, array $data): array;

    /**
     * Get query parameter validation rules.
     *
     * @return array
     */
    abstract protected function queryRules(): array;

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
    public function supportsClientIds(): bool
    {
        if (is_bool($this->clientIds)) {
            return $this->clientIds;
        }

        return $this->clientIds = collect($this->rules(null, []))
            ->has('id');
    }

    /**
     * @inheritDoc
     */
    public function create(array $document): ValidatorInterface
    {
        return $this->validatorForResource(
            $data = $this->dataForCreate($document),
            $this->rules(null, $data),
            $this->messages(),
            $this->attributes()
        );
    }

    /**
     * @inheritDoc
     */
    public function update($record, array $document): ValidatorInterface
    {
        return $this->validatorForResource(
            $data = $this->dataForUpdate($record, $document),
            $this->rules($record, $data),
            $this->messages($record),
            $this->attributes($record)
        );
    }

    /**
     * @inheritDoc
     */
    public function delete($record): ?ValidatorInterface
    {
        if (!$rules = $this->deleteRules($record)) {
            return null;
        }

        return $this->validatorForDelete(
            $this->dataForDelete($record),
            $rules,
            $this->deleteMessages($record),
            $this->deleteAttributes($record)
        );
    }

    /**
     * @inheritDoc
     */
    public function modifyRelationship($record, string $field, array $document): ValidatorInterface
    {
        $resource = ResourceObject::create($this->dataForRelationship($record, $field, $document));

        return $this->factory->createRelationshipValidator(
            $resource,
            $this->relationshipRules($record, $field, $resource->all()),
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
        return $this->validatorForQuery(
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
     * Get custom messages for a resource object validator.
     *
     * @param mixed|null $record
     * @return array
     */
    protected function messages($record = null): array
    {
        return $this->messages;
    }

    /**
     * Get custom attributes for a resource object validator.
     *
     * @param mixed|null $record
     * @return array
     */
    protected function attributes($record = null): array
    {
        return $this->attributes;
    }

    /**
     * Get rules for a delete resource validator.
     *
     * @param $record
     * @return array|null
     *      the rules, or an empty value to indicate no validation.
     * @todo 2.0.0 make this abstract.
     */
    protected function deleteRules($record): ?array
    {
        return [];
    }

    /**
     * Get custom messages for a delete resource validator.
     *
     * @param $record
     * @return array
     */
    protected function deleteMessages($record): array
    {
        return $this->deleteMessages;
    }

    /**
     * Get custom attributes for a delete resource validator.
     *
     * @param $record
     * @return array
     */
    protected function deleteAttributes($record): array
    {
        return $this->deleteAttributes;
    }

    /**
     * Get validation data for creating a domain record.
     *
     * @param array $document
     * @return array
     */
    protected function dataForCreate(array $document): array
    {
        return $document['data'] ?? [];
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
     * merge attributes provided by the client over the top of the existing attribute
     * values.
     *
     * @param mixed $record
     *      the record being updated.
     * @param array $document
     *      the JSON API document to validate.
     * @return array
     */
    protected function dataForUpdate($record, array $document): array
    {
        $resource = $document['data'] ?? [];

        if ($this->mustValidateExisting($record, $document)) {
            $resource['attributes'] = $this->extractAttributes(
                $record,
                $resource['attributes'] ?? []
            );

            $resource['relationships'] = $this->extractRelationships(
                $record,
                $resource['relationships'] ?? []
            );

            /** @see https://github.com/cloudcreativity/laravel-json-api/issues/576 */
            $resource = json_decode(json_encode($resource), true);
        }

        return $resource;
    }

    /**
     * Get validation data for deleting a domain record.
     *
     * @param $record
     * @return array
     */
    protected function dataForDelete($record): array
    {
        $schema = $this->container->getSchema($record);

        return ResourceObject::create([
            'type' => $schema->getResourceType(),
            'id' => $schema->getId($record),
            'attributes' => $schema->getAttributes($record),
            'relationships' => collect($this->existingRelationships($record))->all(),
        ])->all();
    }

    /**
     * Should existing resource values be provided to the validator for an update request?
     *
     * Child classes can overload this method if they need to programmatically work out
     * if existing values must be provided to the validator instance for an update request.
     *
     * @param mixed $record
     *      the record being updated
     * @param array $document
     *      the JSON API document provided by the client.
     * @return bool
     */
    protected function mustValidateExisting($record, array $document): bool
    {
        return false !== $this->validateExisting;
    }

    /**
     * Extract attributes for a resource update.
     *
     * @param $record
     * @param array $new
     * @return array
     */
    protected function extractAttributes($record, array $new): array
    {
        return collect($this->existingAttributes($record))->merge($new)->all();
    }

    /**
     * Get any existing attributes for the provided record.
     *
     * @param $record
     * @return iterable
     */
    protected function existingAttributes($record): iterable
    {
        return $this->container->getSchema($record)->getAttributes($record);
    }

    /**
     * Extract relationships for a resource update.
     *
     * @param $record
     * @param array $new
     * @return array
     */
    protected function extractRelationships($record, array $new): array
    {
        return collect($this->existingRelationships($record))->map(function ($value) {
            return $this->convertExistingRelationships($value);
        })->merge($new)->all();
    }

    /**
     * Get any existing relationships for the provided record.
     *
     * As there is no reliable way for us to work this out (as we do not
     * know the relationship keys), child classes should overload this method
     * to add existing relationship data.
     *
     * @param $record
     * @return iterable
     */
    protected function existingRelationships($record): iterable
    {
        return [];
    }

    /**
     * Get validation data for modifying a relationship.
     *
     * @param mixed $record
     * @param string $field
     * @param array $document
     * @return array
     */
    protected function dataForRelationship($record, string $field, array $document): array
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
     * @param mixed $record
     * @param string $field
     * @param array $data
     * @return array
     */
    protected function relationshipRules($record, string $field, array $data): array
    {
        return collect($this->rules($record, $data))->filter(function ($v, $key) use ($field) {
            return Str::startsWith($key, $field);
        })->all();
    }

    /**
     * Create a validator for a JSON API resource object.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return ValidatorInterface
     */
    protected function validatorForResource(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): ValidatorInterface
    {
        return $this->factory->createResourceValidator(
            ResourceObject::create($data),
            $rules,
            $messages,
            $customAttributes
        );
    }

    /**
     * Create a validator for a delete request.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return ValidatorInterface
     */
    protected function validatorForDelete(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): ValidatorInterface
    {
        return $this->factory->createDeleteValidator($data, $rules, $messages, $customAttributes);
    }

    /**
     * Create a generic validator.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return ValidatorInterface
     */
    protected function createValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): ValidatorInterface
    {
        return $this->factory->createValidator($data, $rules, $messages, $customAttributes);
    }

    /**
     * @return array
     */
    protected function allQueryRules(): array
    {
        return collect($this->defaultQueryRules())
            ->merge($this->queryRules())
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
                'nullable',
                'string',
                $this->allowedIncludePaths(),
            ],
            'page' => [
                'bail',
                'array',
                $this->allowedPagingParameters(),
            ],
            'sort' => [
                'bail',
                'nullable',
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
            return $this->allQueryRules();
        }

        return collect($this->allQueryRules())->reject(function ($value, $key) use ($keys) {
            return Str::startsWith($key, $keys);
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
    protected function validatorForQuery(
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

        return $this->validatorForQuery(
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

    /**
     * Convert relationships returned by the `existingRelationships()` method.
     *
     * We support the method returning JSON API formatted relationships, e.g.:
     *
     * ```
     * return [
 *          'author' => [
     *          'data' => [
     *              'type' => 'users',
     *              'id' => (string) $record->author->getRouteKey(),
     *          ]
     *      ],
     * ];
     * ```
     *
     * Or this shorthand:
     *
     * ```php
     * return [
     *      'author' => $record->author,
     * ];
     * ```
     *
     * This method converts the shorthand into the JSON API formatted relationships.
     *
     * @param $value
     * @return array
     */
    private function convertExistingRelationships($value)
    {
        if (is_array($value) && array_key_exists('data', $value)) {
            return $value;
        }

        if (is_null($value)) {
            return ['data' => null];
        }

        if (is_object($value) && !is_iterable($value)) {
            $schema = $this->container->getSchema($value);

            return [
                'data' => [
                    'type' => $schema->getResourceType(),
                    'id' => $schema->getId($value),
                ],
            ];
        }

        $data = collect($value)->map(function ($v) {
            $schema = $this->container->getSchema($v);

            return ['type' => $schema->getResourceType(), 'id' => $schema->getId($v)];
        })->all();

        return compact('data');
    }
}
