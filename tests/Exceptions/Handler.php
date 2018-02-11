<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Exceptions;

use CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors;
use Exception;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Orchestra\Testbench\Exceptions\Handler as BaseHandler;

class Handler extends BaseHandler
{

    use HandlesErrors;

    /**
     * @var bool
     */
    private $report = false;

    /**
     * @return $this
     */
    public function throwExceptions()
    {
        $this->report = true;

        return $this;
    }

    /**
     * @param Exception $e
     * @throws Exception
     */
    public function report(Exception $e)
    {
        if (true === $this->report && !$e instanceof JsonApiException) {
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
