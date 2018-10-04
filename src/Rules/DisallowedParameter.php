<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class DisallowedParameter
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class DisallowedParameter implements Rule
{

    /**
     * @var string
     */
    private $name;

    /**
     * DisallowedParameter constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function passes($attribute, $value)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return trans('jsonapi::validation.disallowed_parameter', [
            'name' => $this->name,
        ]);
    }

}
