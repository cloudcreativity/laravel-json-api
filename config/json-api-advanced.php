<?php

use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface as Codec;
use CloudCreativity\JsonApi\Contracts\Repositories\EncodersRepositoryInterface as Enc;
use CloudCreativity\JsonApi\Contracts\Repositories\DecodersRepositoryInterface as Dec;
use CloudCreativity\JsonApi\Exceptions\RenderContainer as Ex;
use CloudCreativity\JsonApi\Config as C;

return [

    /**
     * Whether every route in the application is a JSON API endpoint.
     *
     * If true, will install the 'json-api' middleware on the HTTP Kernel.
     */
    C::IS_GLOBAL => true,

    /**
     * Codec Matchers
     */
    C::CODEC_MATCHER => [
        // @todo
    ],

    /**
     * Encoders
     */
    C::ENCODERS => [
        // @todo
    ],

    /**
     * Decoders
     */
    C::DECODERS => [
        // @todo
    ],

    /**
     * Exception Render Container
     */
    C::EXCEPTIONS => [
        Ex::HTTP_CODE_MAPPING => [
            'SomeExceptionClass' => 422,
        ],
    ],
];
