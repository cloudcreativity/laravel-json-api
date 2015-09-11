<?php

namespace CloudCreativity\JsonApi;

class Middleware
{

    /** The middleware name to boot JSON API support for route(s) */
    const JSON_API = 'json-api';

    /** The middleware name for registering supported extensions */
    const SUPPORTED_EXT = 'json-api-supported-ext';

    /**
     * @return string
     */
    public static function ext()
    {
        $extensions = func_get_args();

        return ($extensions) ?
            sprintf('%s:%s', static::SUPPORTED_EXT, implode(',', $extensions)) :
            static::SUPPORTED_EXT;
    }

}
