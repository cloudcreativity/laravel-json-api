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

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validation rules for this package.
    |
    */

    'allowed_field_sets' => [
        'default' => 'Sparse field sets must contain only allowed ones.',
        'singular' => 'Sparse field set :values is not allowed.',
        'plural' => 'Sparse field sets :values are not allowed.',
    ],

    'allowed_filter_parameters' => [
        'default' => 'Filter parameters must contain only allowed ones.',
        'singular' => 'Filter parameter :values is not allowed.',
        'plural' => 'Filter parameters :values are not allowed.',
    ],

    'allowed_include_paths' => [
        'default' => 'Include paths must contain only allowed ones.',
        'singular' => 'Include path :values is not allowed.',
        'plural' => 'Include paths :values are not allowed.',
    ],

    'allowed_sort_parameters' => [
        'default' => 'Sort parameters must contain only allowed ones.',
        'singular' => 'Sort parameter :values is not allowed.',
        'plural' => 'Sort parameters :values are not allowed.',
    ],

    'allowed_page_parameters' => [
        'default' => 'Page parameters must contain only allowed ones.',
        'singular' => 'Page parameter :values is not allowed.',
        'plural' => 'Page parameters :values are not allowed.',
    ],

    'date_time_iso_8601' => 'The :attribute is not a valid ISO 8601 date and time.',

    'disallowed_parameter' => 'Parameter :name is not allowed.',

    'has_one' => 'The :attribute field must be a to-one relationship containing :types resources.',

    'has_many' => 'The :attribute field must be a to-many relationship containing :types resources.',
];
