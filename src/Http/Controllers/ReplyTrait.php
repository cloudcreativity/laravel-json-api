<?php

namespace CloudCreativity\JsonApi\Http\Controllers;

use App;
use CloudCreativity\JsonApi\Http\Responses\ResponsesHelper;

/**
 * Class ReplyTrait
 * @package CloudCreativity\JsonApi
 */
trait ReplyTrait
{

    /**
     * @return ResponsesHelper
     */
    public function reply()
    {
        return App::make(ResponsesHelper::class);
    }
}
