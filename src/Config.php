<?php

namespace CloudCreativity\JsonApi;

/**
 * Class Config
 * @package CloudCreativity\JsonApi
 */
class Config
{

    /** The config file name, and the name of the route middleware. */
    const NAME = 'json-api';

    /** If a true boolean, Json Api support will be initialised for the entire application. */
    const IS_GLOBAL = 'is-global';

    /** The key for codec matchers configuration */
    const CODEC_MATCHER = 'codec-matcher';

    /** The key for the schemas configuration */
    const SCHEMAS = 'schemas';

    /** The key for Exception Renderer Container config */
    const EXCEPTIONS = 'exceptions';
}
