<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

/**
 * Class AllowedFieldSets
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class AllowedFieldSets implements Rule
{

    /**
     * @var bool
     */
    private $all;

    /**
     * @var Collection|null
     */
    private $allowed;

    /**
     * AllowedFieldSets constructor.
     *
     * @param array|null $allowed
     */
    public function __construct(array $allowed = null)
    {
        $this->all = is_null($allowed);
        $this->allowed = collect($allowed);
    }

    /**
     * Allow fields for a resource type.
     *
     * @param string $resourceType
     * @param string[]|null $fields
     *      the allowed fields, empty array for none allowed, or null for all allowed.
     * @return $this
     */
    public function allow(string $resourceType, array $fields = null): self
    {
        $this->all = false;
        $this->allowed[$resourceType] = $fields;

        return $this;
    }

    /**
     * Allow any fields for the specified resource type.
     *
     * @param string ...$resourceTypes
     * @return $this
     */
    public function any(string ...$resourceTypes): self
    {
        foreach ($resourceTypes as $resourceType) {
            $this->allow($resourceType, null);
        }

        return $this;
    }

    /**
     * Allow no fields for the specified resource type.
     *
     * @param string ...$resourceTypes
     * @return $this
     */
    public function none(string ...$resourceTypes): self
    {
        foreach ($resourceTypes as $resourceType) {
            $this->allow($resourceType, []);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function passes($attribute, $value)
    {
        if ($this->all) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        return collect($value)->every(function ($value, $key) {
            return $this->allowed($key, (string) $value);
        });
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return trans('jsonapi::validation.allowed_field_sets');
    }

    /**
     * Are the fields allowed for the specified resource type?
     *
     * @param $resourceType
     * @param string $fields
     * @return bool
     */
    protected function allowed($resourceType, string $fields): bool
    {
        if (!$this->allowed->has($resourceType)) {
            return false;
        }

        $allowed = $this->allowed->get($resourceType);

        if (is_null($allowed)) {
            return true;
        }

        $allowed = collect((array) $allowed);

        return collect(explode(',', $fields))->every(function ($value) use ($allowed) {
            return $allowed->contains($value);
        });
    }

}
