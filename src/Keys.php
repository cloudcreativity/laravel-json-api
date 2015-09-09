<?php

namespace CloudCreativity\JsonApi;

/**
 * Class Config
 * @package CloudCreativity\JsonApi
 */
class Keys
{

    /** The config file name, and the name of the route middleware. */
    const NAME = 'json-api';

    /** If a true boolean, Json Api support will be initialised for the entire application. */
    const IS_GLOBAL = 'is-global';

    /** The key for schema definitions */
    const SCHEMAS = 'schemas';

    /** The key for encoder options */
    const ENCODER_OPTIONS = 'encoder-options';

    /** The ket for the codec matcher configuration */
    const CODEC_MATCHER = 'codec-matcher';

    /** The key for Exception Render Container config */
    const EXCEPTIONS = 'exceptions';
}
