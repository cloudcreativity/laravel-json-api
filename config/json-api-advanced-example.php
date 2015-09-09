<?php

use CloudCreativity\JsonApi\Contracts\Config\CodecMatcherRepositoryInterface as CM;
use CloudCreativity\JsonApi\Contracts\Config\EncoderOptionsRepositoryInterface as Enc;
use CloudCreativity\JsonApi\Contracts\Config\SchemasRepositoryInterface as Sch;
use CloudCreativity\JsonApi\Exceptions\RenderContainer as Ex;
use CloudCreativity\JsonApi\Keys as C;

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
        // The default codec matcher.
        CM::DEFAULTS => [
            CM::ENCODERS => [
                // These will use the default schemas and encoder options
                'application/vnd.api+json',
                'application/vnd.api+json;charset=utf-8',
            ],
            CM::DECODERS => [
                // These will use the default decoder.
                'application/vnd.api+json',
                'application/vnd.api+json;charset=utf-8',
            ],
        ],
        // A codec matcher that adds support for 'application/json' to the defaults.
        'with-json' => [
            CM::ENCODERS => [
                'application/json' => [
                    // Use the schemas known as 'extras'
                    CM::ENCODER_SCHEMAS => 'extras',
                    // Use the encoder options known as 'json' instead of the default encoder options.
                    CM::ENCODER_OPTIONS => 'json',
                ],
            ],
        ],
        // A codec matcher that adds text encoding to the defaults.
        'humanized' => [
            CM::ENCODERS => [
                'text/plain' => [
                    CM::ENCODER_OPTIONS => 'pretty-print',
                ],
            ],
        ],
    ],

    /**
     * Encoder Options
     */
    C::ENCODER_OPTIONS => [
        Enc::DEFAULTS => [
            Enc::IS_SHOW_VERSION_INFO => true,
            Enc::VERSION_META => [
                'version' => '1.0',
            ],
        ],
        // these named options override the defaults.
        'with-json' => [
            Enc::OPTIONS => JSON_BIGINT_AS_STRING,
        ],
        'pretty-print' => [
            Enc::OPTIONS => JSON_PRETTY_PRINT,
            // recursively merged on top
            Enc::VERSION_META => [
                'hello' => 'world!',
            ],
        ],
    ],

    /**
     * Schemas
     */
    C::SCHEMAS => [
        Sch::DEFAULTS => [
            'Author' => 'AuthorSchema',
            'Post' => 'PostSchema',
            'Comment' => 'CommentSchema',
        ],
        // These schemas are added to the defaults
        'extras' => [
            'Tag' => 'TagSchema',
        ],
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
