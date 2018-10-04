<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

abstract class AbstractAllowedRule implements Rule
{

    /**
     * @var bool
     */
    private $all;

    /**
     * @var Collection
     */
    private $allowed;

    /**
     * The last value that was validated.
     *
     * @var string|null
     */
    private $value;

    /**
     * Extract parameters from the value.
     *
     * @param mixed $value
     * @return Collection
     */
    abstract protected function extract($value): Collection;

    /**
     * AllowedFilterParameters constructor.
     *
     * @param array|null $allowed
     */
    public function __construct(array $allowed = null)
    {
        $this->all = is_null($allowed);
        $this->allowed = collect($allowed)->combine($allowed);
    }

    /**
     * Add allowed parameters.
     *
     * @param string ...$params
     * @return $this
     */
    public function allow(string ...$params): self
    {
        $this->all = false;

        foreach ($params as $param) {
            $this->allowed->put($param, $param);
        }

        return $this;
    }

    /**
     * Forget an allowed parameter.
     *
     * @param string ...$params
     * @return $this
     */
    public function forget(string ...$params): self
    {
        $this->allowed->forget($params);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function passes($attribute, $value)
    {
        $this->value = $value;

        if ($this->all) {
            return true;
        }

        return $this->extract($value)->every(function ($key) {
            return $this->allowed($key);
        });
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        $name = snake_case(class_basename($this));
        $invalid = $this->invalid();

        if ($invalid->isEmpty()) {
            $key = 'default';
        } else {
            $key = (1 === $invalid->count()) ? 'singular' : 'plural';
        }

        return trans("jsonapi::validation.{$name}.{$key}", [
            'values' => $params = $invalid->implode(', '),
        ]);
    }

    /**
     * Is the parameter allowed?
     *
     * @param string $param
     * @return bool
     */
    protected function allowed(string $param): bool
    {
        return $this->allowed->has($param);
    }

    /**
     * @return Collection
     */
    protected function invalid()
    {
        return $this->extract($this->value)->reject(function ($value) {
            return $this->allowed($value);
        });
    }

}
