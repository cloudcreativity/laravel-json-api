<?php

namespace CloudCreativity\JsonApi\Config;

/**
 * Class Config
 * @package CloudCreativity\JsonApi
 */
class Config
{

    /** The config file name. */
    const KEY = 'json-api';

    /** If a true boolean, Json Api support will be initialised for the entire application. */
    const IS_GLOBAL = 'is-global';

    /** The key for schema definitions */
    const SCHEMAS = 'schemas';

    /** The key for encoder options */
    const ENCODER_OPTIONS = 'encoder-options';

    /** The ket for the codec matcher configuration */
    const CODEC_MATCHER = 'codec-matcher';

    /** The key for Exception Render Container config */
    const EXCEPTION_RENDER_CONTAINER = 'exception-render-container';
}
