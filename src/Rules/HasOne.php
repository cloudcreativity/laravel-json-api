<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class HasOne implements Rule
{

    /**
     * @var string[]
     */
    private $types;

    /**
     * HasOne constructor.
     *
     * @param string ...$types
     *      the expected resource types.
     */
    public function __construct(string ...$types)
    {
        $this->types = $types;
    }

    /**
     * @inheritDoc
     */
    public function passes($attribute, $value)
    {
        if (!is_null($value) && !is_array($value)) {
            return false;
        }

        if (empty($this->types)) {
            $this->types = [Str::plural($attribute)];
        }

        return $this->accept($value);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        $key = 'jsonapi::validation.' . Str::snake(class_basename($this));;

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
        if (is_null($data)) {
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

}
