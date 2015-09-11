<?php

namespace CloudCreativity\JsonApi\Http\Middleware;

use Closure;
use CloudCreativity\JsonApi\Services\EnvironmentService;
use Neomerx\JsonApi\Parameters\SupportedExtensions;

class SupportedExt
{

    /**
     * @var EnvironmentService
     */
    private $env;

    /**
     * @param EnvironmentService $env
     */
    public function __construct(EnvironmentService $env)
    {
        $this->env = $env;
    }

    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $args = array_slice(func_get_args(), 2);
        $exts = implode(',', $args);

        if ($exts) {
            $this->env->registerSupportedExtensions(new SupportedExtensions($exts));
        }

        return $next($request);
    }
}
