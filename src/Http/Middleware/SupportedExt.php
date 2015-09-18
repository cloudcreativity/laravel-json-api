<?php

namespace CloudCreativity\JsonApi\Http\Middleware;

use Closure;
use CloudCreativity\JsonApi\Contracts\Integration\EnvironmentInterface;
use CloudCreativity\JsonApi\Integration\EnvironmentService;
use Neomerx\JsonApi\Parameters\SupportedExtensions;
use RuntimeException;

class SupportedExt
{

    /**
     * @var EnvironmentService
     */
    private $environment;

    /**
     * @param EnvironmentInterface $env
     */
    public function __construct(EnvironmentInterface $env)
    {
        if (!$env instanceof EnvironmentService) {
            throw new RuntimeException(sprintf('%s is built to work with the %s instance of %s.', static::class, EnvironmentService::class, EnvironmentInterface::class));
        }

        $this->environment = $env;
    }

    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $args = array_slice(func_get_args(), 2);
        $extensions = implode(',', $args);

        if ($extensions) {
            $this->environment->registerSupportedExtensions(new SupportedExtensions($extensions));
        }

        return $next($request);
    }
}
