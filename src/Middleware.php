<?php

namespace CloudCreativity\JsonApi;

class Middleware
{

    /** The middleware name to boot JSON API support for route(s) */
    const JSON_API = 'json-api';

    /** The middleware name for registering supported extensions */
    const SUPPORTED_EXT = 'json-api-supported-ext';

    /**
     * @param string|null $codecMatcherName
     * @return string
     */
    public static function jsonApi($codecMatcherName = null)
    {
        return ($codecMatcherName) ? sprintf('%s:%s', static::JSON_API, $codecMatcherName) : static::JSON_API;
    }

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
