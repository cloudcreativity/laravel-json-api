<?php

use CloudCreativity\JsonApi\Config as C;
use CloudCreativity\JsonApi\Contracts\Error\ErrorObjectInterface as Error;
use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface as Codec;
use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface as Schemas;
use CloudCreativity\JsonApi\Decoders\DocumentDecoder;
use CloudCreativity\JsonApi\Exceptions\StandardRenderer as Renderer;

return [

    /**
     * Whether every route in the application is a JSON API endpoint.
     *
     * If true, will install the 'json-api' middleware on the HTTP Kernel.
     */
    C::IS_GLOBAL => false,

    /**
     * Codec Matchers
     */
    C::CODEC_MATCHER => [
        Codec::ENCODERS => [
            'application/vnd.api+json',
            'text/plain' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ],
        Codec::DECODERS => [
            'application/vnd.api+json' => DocumentDecoder::class,
        ],
    ],

    /**
     * Schemas
     */
    C::SCHEMAS => [
        Schemas::DEFAULTS => [
            'Article' => 'ArticleSchema',
            'Comment' => 'CommentSchema',
        ],
        // merged with defaults if JSON API middleware uses the 'extra-schemas' name.
        'extra-schemas' => [
            'Person' => 'PersonSchema',
        ],
    ],

    /**
     * Exception Render Container
     */
    C::EXCEPTIONS => [

        /**
         * The default status that should be used if an exception is not recognised by the renderer.
         */
        Renderer::DEFAULT_STATUS => 500,

        /**
         * A map of exception classes to either a HTTP status, or an array representing a JSON API error object. Example below.
         *
         * Note that there is no need to register exception clases that render the following interfaces, as the renderer knows how to handle them:
         *
         * `Neomerx\JsonApi\Contracts\Document\ErrorInterface`
         * `CloudCreativity\JsonApi\Contracts\Error\ErrorsAwareInterface`
         */
        Renderer::MAP => [
            'FooException' => 503,
            'BarException' => [
                Error::TITLE => "Teapot",
                Error::DETAIL => "I'm a teapot, not a server.",
                Error::STATUS => 418,
            ],
        ],
    ],
];
