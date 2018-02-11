<?php

namespace App\Exceptions;

use CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors;
use Exception;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Orchestra\Testbench\Exceptions\Handler as BaseHandler;

class Handler extends BaseHandler
{

    use HandlesErrors;

    /**
     * @param Exception $e
     * @throws Exception
     */
    public function report(Exception $e)
    {
        if (!$e instanceof JsonApiException) {
            throw $e;
        }

        parent::report($e);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Exception $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, \Exception $e)
    {
        if ($this->isJsonApi()) {
            return $this->renderJsonApi($request, $e);
        }

        return parent::render($request, $e);
    }
}
