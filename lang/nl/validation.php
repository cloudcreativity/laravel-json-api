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
        'default' => 'Spaarzame veldsets mogen alleen toegestane bevatten.',
        'singular' => 'Spaarzame veldset :values is niet toegestaan.',
        'plural' => 'Spaarzame veldsets :values zijn niet toegestaan.',
    ],

    'allowed_filter_parameters' => [
        'default' => 'Filterparameters mogen alleen toegestane bevatten.',
        'singular' => 'Filterparameter :values is niet toegestaan.',
        'plural' => 'Filterparameters :values zijn niet toegestaan.',
    ],

    'allowed_include_paths' => [
        'default' => 'Insluit-paden mogen alleen toegestane bevatten.',
        'singular' => 'Insluit-pad :values is niet toegestaan.',
        'plural' => 'Insluit-paden :values zijn niet toegestaan.',
    ],

    'allowed_sort_parameters' => [
        'default' => 'Sorteerparameters mogen alleen toegestane bevatten.',
        'singular' => 'Sorteerparameter :values is niet toegestaan.',
        'plural' => 'Sorteerparameters :values zijn niet toegestaan.',
    ],

    'allowed_page_parameters' => [
        'default' => 'Pagina-parameters mogen alleen toegestane bevatten.',
        'singular' => 'Pagina-parameter :values is niet toegestaan.',
        'plural' => 'Pagina-parameters :values zijn niet toegestaan.',
    ],

    'date_time_iso_8601' => 'Het attribuut :attribute heeft geen geldig ISO 8601 datum/tijd formaat.',

    'disallowed_parameter' => 'Parameter :name is niet toegestaan.',

    'has_one' => 'Het veld :attribute moet een naar-één relatie zijn die :types resources bevat.',

    'has_many' => 'Het veld :attribute moet een naar-velen relatie zijn die :types resources bevat.',
];
