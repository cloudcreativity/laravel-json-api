<?php

use CloudCreativity\JsonApi\Keys as C;
use CloudCreativity\JsonApi\Contracts\Config\CodecMatcherRepositoryInterface as CM;
use CloudCreativity\JsonApi\Contracts\Config\EncoderOptionsRepositoryInterface as Enc;
use CloudCreativity\JsonApi\Exceptions\RenderContainer as Ex;

return [

    /**
     * Whether every route in the application is a JSON API endpoint.
     *
     * If true, will install the 'json-api' middleware on the HTTP Kernel.
     */
    C::IS_GLOBAL => true,

    /**
     * Codec Matcher
     */
    C::CODEC_MATCHER => [
        CM::ENCODERS => [
            'application/vnd.api+json',
            'application/vnd.api+json;charset=utf-8',
            'application/json',
        ],
        CM::DECODERS => [
            'application/vnd.api+json',
            'application/vnd.api+json;charset=utf-8',
        ],
    ],

    /**
     * Encoder Options
     */
    C::ENCODER_OPTIONS => [
        Enc::OPTIONS => JSON_PRETTY_PRINT,
        Enc::DEPTH => 250,
    ],

    /**
     * Schemas
     */
    C::SCHEMAS => [
        'Author' => 'AuthorSchema',
        'Post' => 'PostSchema',
        'Comment' => 'CommentSchema',
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
