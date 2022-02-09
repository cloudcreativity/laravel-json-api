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
