<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Contracts\Document\MutableErrorInterface as Error;
use CloudCreativity\LaravelJsonApi\Http\Headers\RestrictiveHeadersChecker as H;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;

/** @deprecated 2.0.0 */

return [

    /**
     * The client's `Accept` header does not a configured encoder.
     */
    H::NOT_ACCEPTABLE => [
        Error::TITLE => 'Not Acceptable',
        Error::STATUS => Response::HTTP_NOT_ACCEPTABLE,
    ],

    /**
     * The client's `Content-Type` header contains multiple media types, so we do not
     * know which media type to match against.
     */
    H::MULTIPLE_MEDIA_TYPES => [
        Error::TITLE => 'Invalid Content-Type Header',
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * The client's `Content-Type` header does not match a configured decoder.
     */
    H::UNSUPPORTED_MEDIA_TYPE => [
        Error::TITLE => 'Invalid Content-Type Header',
        Error::STATUS => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
        Error::DETAIL => 'The specified content type is not supported.',
    ],

    /**
     * Error used when a user is not authenticated.
     */
    AuthenticationException::class => [
        Error::TITLE => 'Unauthenticated',
        Error::STATUS => Response::HTTP_UNAUTHORIZED,
    ],

    /**
     * Error used when a request is not authorized.
     */
    AuthorizationException::class => [
        Error::TITLE => 'Unauthorized',
        Error::STATUS => Response::HTTP_FORBIDDEN,
    ],

    /**
     * Error used when the CSRF token is invalid.
     */
    TokenMismatchException::class => [
        Error::TITLE => 'Invalid Token',
        Error::DETAIL => 'The token is not valid.',
        Error::STATUS => '419',
    ],

    /**
     * Error used when converting a Laravel validation exception outside of JSON API validation.
     */
    ValidationException::class => [
        Error::STATUS => '422',
    ],

    /**
     * Exceptions
     *
     * To register errors for specific exceptions, use the fully qualified exception class as the
     * key. The default exception parser will use the error below for the generic `Exception` class if
     * there is no error registered for an exception class that it is parsing.
     */
    Exception::class => [
        Error::TITLE => 'Internal Server Error',
        Error::STATUS => Response::HTTP_INTERNAL_SERVER_ERROR,
    ],
];
