<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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
    | Error Object Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default translatable members
    | of JSON API error objects. According to the JSON API spec, the
    | `title` and `detail` members can be localized. In addition the `code`
    | member is also read from this package if you want to give the error
    | a specific code.
    |
    | Set any value to an empty string if you do not want the member to be
    | included in the error object.
    |
    | @see http://jsonapi.org/format/#errors
    */

    'member_required' => [
        'title' => 'Non-Compliant JSON API Document',
        'detail' => "The member :member is required.",
        'code' => '',
    ],

    'member_object_expected' => [
        'title' => 'Non-Compliant JSON API Document',
        'detail' => "The member :member must be an object.",
        'code' => '',
    ],

    'member_string_expected' => [
        'title' => 'Non-Compliant JSON API Document',
        'detail' => "The member :member must be a string.",
        'code' => '',
    ],

    'member_empty' => [
        'title' => 'Non-Compliant JSON API Document',
        'detail' => "The member :member cannot be empty.",
        'code' => '',
    ],

    'resource_type_not_supported' => [
        'title' => 'Not Supported',
        'detail' => "Resource type :type is not supported by this endpoint.",
        'code' => '',
    ],

    'resource_type_not_recognised' => [
        'title' => 'Not Supported',
        'detail' => "Resource type :type is not recognised.",
        'code' => '',
    ],

    'resource_id_not_supported' => [
        'title' => 'Not Supported',
        'detail' => "Resource id :id is not supported by this endpoint.",
        'code' => '',
    ],

    'resource_exists' => [
        'title' => 'Conflict',
        'detail' => "Resource :id already exists.",
        'code' => '',
    ],

    'resource_not_found' => [
        'title' => 'Not Found',
        'detail' => "The related resource does not exist.",
        'code' => '',
    ],

    'resource_invalid' => [
        'title' => 'Unprocessable Entity',
        'detail' => 'The document was well-formed but contains semantic errors.',
        'code' => '',
    ],

    'query_invalid' => [
        'title' => 'Invalid Query Parameter',
        'detail' => 'The request query parameters are invalid.',
        'code' => '',
    ],
];
