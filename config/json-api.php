<?php

use CloudCreativity\JsonApi\Config as C;
use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface as Codec;
use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface as Schemas;
use CloudCreativity\JsonApi\Decoders\DocumentDecoder;

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
            Article::class => ArticlesSchema::class,
            Comment::class => CommentsSchema::class,
        ],
        // merged with defaults if JSON API middleware uses the 'extra-schemas' name.
        'extra-schemas' => [
            'Person' => 'PersonSchema',
        ],
    ],
];
