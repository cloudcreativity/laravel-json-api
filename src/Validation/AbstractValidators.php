<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Http\Query\QueryCheckerInterface;

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
     * Whether unrecognized query parameters should be allowed.
     *
     * @var bool
     */
    protected $allowUnrecognizedParameters = false;

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
     * What field sets the client is allowed to request per JSON API resource object type.
     *
     * Null = the client can specify any fields for any resource object type.
     * Empty array = the client cannot specify any fields for any resource object type (i.e. all denied.)
     * Non-empty array = configuration per JSON API resource object type. The key should be the type, the value should
     * be either null (all fields allowed for that type), empty array (no fields allowed for that type) or an array
     * of string values listing the allowed fields for that type.
     *
     * @var array|null
     */
    protected $allowedFieldSetTypes;

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
    public function createResource(array $document)
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
    public function updateResource($record, array $document)
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
    public function modifyRelationship($record, $field, array $document)
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
    public function fetchManyQueryChecker(array $params)
    {
        $validator = $this->queryValidator($params);

        return $this->factory->createValidationQueryChecker(
            $validator,
            $this->allowUnrecognizedParameters(),
            $this->allowedIncludePaths(),
            $this->allowedFieldSetTypes(),
            $this->allowedSortParameters(),
            $this->allowedPagingParameters(),
            $this->allowedFilteringParameters()
        );
    }

    /**
     * @inheritDoc
     */
    public function fetchQueryChecker(array $params)
    {
        /**
         * Allow filter params, but not sort and page, for a resource GET.
         *
         * @see https://github.com/cloudcreativity/laravel-json-api/issues/218
         */
        $validator = $this->queryValidatorWithoutSortAndPage($params);

        return $this->factory->createValidationQueryChecker(
            $validator,
            $this->allowUnrecognizedParameters(),
            $this->allowedIncludePaths(),
            $this->allowedFieldSetTypes(),
            [],
            [],
            $this->allowedFilteringParametersWithoutId()
        );
    }

    /**
     * @inheritDoc
     */
    public function modifyQueryChecker(array $params)
    {
        /** For modify resource requests, do not allow filter, sort and page. */
        $validator = $this->queryValidatorWithoutFilterSortAndPage($params);

        return $this->factory->createValidationQueryChecker(
            $validator,
            $this->allowUnrecognizedParameters(),
            $this->allowedIncludePaths(),
            $this->allowedFieldSetTypes(),
            [],
            [],
            []
        );
    }

    /**
     * @inheritDoc
     */
    public function fetchRelatedQueryChecker(array $params)
    {
        return $this->fetchManyQueryChecker($params);
    }

    /**
     * @inheritDoc
     */
    public function fetchRelationshipQueryChecker(array $params)
    {
        $validator = $this->queryValidator($params);

        /** As we are only getting resource identifiers, include and fieldsets are not supported. */
        return $this->factory->createValidationQueryChecker(
            $validator,
            $this->allowUnrecognizedParameters(),
            [],
            [],
            $this->allowedSortParameters(),
            $this->allowedPagingParameters(),
            $this->allowedFilteringParameters()
        );
    }

    /**
     * @inheritDoc
     */
    public function modifyRelationshipQueryChecker(array $params)
    {
        return $this->fetchRelationshipQueryChecker($params);
    }

    /**
     * Get custom messages for a resource validator.
     *
     * @return array
     */
    protected function messages()
    {
        return $this->messages;
    }

    /**
     * Get custom attributes for a resource validator.
     *
     * @return array
     */
    protected function attributes()
    {
        return $this->attributes;
    }

    /**
     * Get validation data for creating a domain record.
     *
     * @param array $document
     * @return array
     */
    protected function createData(array $document)
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
    protected function updateData($record, array $document)
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
    protected function extractAttributes($record, array $new)
    {
        return $this->currentAttributeValues($record)->merge($new);
    }

    /**
     * Get the current attribute values for the provided record.
     *
     * @param $record
     * @return Collection
     */
    protected function currentAttributeValues($record)
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
    protected function relationshipData($record, $field, array $document)
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
    protected function relationshipRules($record, $field)
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
    ) {
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
    protected function queryRules()
    {
        return $this->queryRules;
    }

    /**
     * Get the validation rules for query parameters, excluding filter, sort and page.
     *
     * @return array
     */
    protected function queryRulesWithoutFilterSortAndPage()
    {
        return collect($this->queryRules())->reject(function ($value, $key) {
            return starts_with($key, ['filter.', 'sort.', 'page.']);
        })->all();
    }

    /**
     * Get the validation rules for query parameters excluding sort and page.
     *
     * @return array
     */
    protected function queryRulesWithoutSortAndPage()
    {
        return collect($this->queryRules())->reject(function ($value, $key) {
            return starts_with($key, ['sort.', 'page.']);
        })->all();
    }

    /**
     * @return array
     */
    protected function queryMessages()
    {
        return $this->queryMessages;
    }

    /**
     * @return array
     */
    protected function queryAttributes()
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
    ) {
        return $this->factory->createQueryValidator($data, $rules, $messages, $customAttributes);
    }


    /**
     * Get a validator for all query parameters.
     *
     * @param array $params
     * @return ValidatorInterface
     */
    protected function queryValidator(array $params)
    {
        return $this->createQueryValidator(
            $params,
            $this->queryRules(),
            $this->queryMessages(),
            $this->queryAttributes()
        );
    }

    /**
     * Get a validator for query parameters excluding filter, sort and page rules.
     *
     * @param array $params
     * @return ValidatorInterface
     */
    protected function queryValidatorWithoutFilterSortAndPage(array $params)
    {
        return $this->createQueryValidator(
            $params,
            $this->queryRulesWithoutFilterSortAndPage(),
            $this->queryMessages(),
            $this->queryAttributes()
        );
    }

    /**
     * Get a validator for query parameters excluding sort and page rules.
     *
     * @param array $params
     * @return ValidatorInterface
     */
    protected function queryValidatorWithoutSortAndPage(array $params)
    {
        return $this->createQueryValidator(
            $params,
            $this->queryRulesWithoutSortAndPage(),
            $this->queryMessages(),
            $this->queryAttributes()
        );
    }

    /**
     * Whether unrecognized parameters should be allowed.
     *
     * @return bool
     */
    protected function allowUnrecognizedParameters()
    {
        return (bool) $this->allowUnrecognizedParameters;
    }

    /**
     * What include paths the client is allowed to request.
     *
     * Empty array = clients are not allowed to specify include paths.
     * Null = all paths are allowed.
     *
     * @return string[]|null
     */
    protected function allowedIncludePaths()
    {
        return $this->allowedIncludePaths;
    }

    /**
     * What field sets the client is allowed to request per JSON API resource object type.
     *
     * Null = the client can specify any fields for any resource object type.
     * Empty array = the client cannot specify any fields for any resource object type (i.e. all denied.)
     * Non-empty array = configuration per JSON API resource object type. The key should be the type, the value should
     * be either null (all fields allowed for that type), empty array (no fields allowed for that type) or an array
     * of string values listing the allowed fields for that type.
     *
     * @return array|null
     */
    protected function allowedFieldSetTypes()
    {
        return $this->allowedFieldSetTypes;
    }

    /**
     * What sort field names can be sent by the client.
     *
     * Empty array = clients are not allowed to specify sort fields.
     * Null = clients can specify any sort fields.
     *
     * @return string[]|null
     */
    protected function allowedSortParameters()
    {
        return $this->allowedSortParameters;
    }

    /**
     * What paging fields can be sent by the client.
     *
     * Empty array = clients are not allowed to request paging.
     * Null = clients can specify any paging fields they want.
     *
     * @return string[]|null
     */
    protected function allowedPagingParameters()
    {
        return $this->allowedPagingParameters;
    }

    /**
     * What filtering fields can be sent by the client.
     *
     * Empty array = clients are not allowed to request filtering.
     * Null = clients can specify any filtering fields they want.
     *
     * @return string[]|null
     */
    protected function allowedFilteringParameters()
    {
        return $this->allowedFilteringParameters;
    }

    /**
     * @return array
     */
    protected function allowedFilteringParametersWithoutId()
    {
        $allowed = $this->allowedFilteringParameters();

        if (is_null($allowed)) {
            return $allowed;
        }

        return collect($this->allowedFilteringParameters())->reject('id')->values()->all();
    }

}
