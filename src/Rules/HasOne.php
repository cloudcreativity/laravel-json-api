<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Contracts\Validation\Rule;

class HasOne implements Rule
{

    /**
     * @var string[]
     */
    private $types;

    /**
     * @var bool
     */
    private $allowEmpty;

    /**
     * HasOne constructor.
     *
     * @param string ...$types
     *      the expected resource types.
     */
    public function __construct(string ...$types)
    {
        $this->types = $types;
        $this->allowEmpty();
    }

    /**
     * Set whether an empty `has-one` relationship is allowed.
     *
     * @param bool $bool
     * @return $this
     */
    public function allowEmpty(bool $bool = true): self
    {
        $this->allowEmpty = $bool;

        return $this;
    }

    /**
     * Set a related resource to always be required.
     *
     * @return $this
     */
    public function required(): self
    {
        return $this->allowEmpty(false);
    }

    /**
     * @inheritDoc
     */
    public function passes($attribute, $value)
    {
        if (!is_null($value) && !is_array($value)) {
            return false;
        }

        return $this->accept($value);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        $key = 'jsonapi::validation.' . Str::underscore(class_basename($this));;

        return trans($key, [
            'types' => collect($this->types)->implode(', '),
        ]);
    }

    /**
     * Accept the data value.
     *
     * @param array|null $data
     * @return bool
     */
    protected function accept(?array $data): bool
    {
        if (is_null($data) && $this->acceptsNone()) {
            return true;
        }

        return $this->acceptType($data);
    }

    /**
     * @param $data
     * @return bool
     */
    protected function acceptType($data): bool
    {
        return is_array($data) && collect($this->types)->containsStrict(
            $data['type'] ?? null
        );
    }

    /**
     * @return bool
     */
    protected function acceptsNone(): bool
    {
        return $this->allowEmpty;
    }

}
