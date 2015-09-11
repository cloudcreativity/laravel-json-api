<?php

namespace CloudCreativity\JsonApi\Http\Controllers;

use CloudCreativity\JsonApi\Contracts\Error\ErrorObjectInterface;
use CloudCreativity\JsonApi\Error\ErrorException;
use Illuminate\Routing\Controller;

class JsonApiController extends Controller
{

    /**
     * @param array $parameters
     * @return void
     * @throws ErrorException
     */
    public function missingMethod($parameters = [])
    {
        throw new ErrorException([
            ErrorObjectInterface::TITLE => 'Not Implemented',
            ErrorObjectInterface::DETAIL => 'This JSON API endpoint is not yet implemented.',
            ErrorObjectInterface::STATUS => 501,
        ]);
    }
}
