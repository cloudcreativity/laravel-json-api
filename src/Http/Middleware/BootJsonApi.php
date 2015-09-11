<?php

namespace CloudCreativity\JsonApi\Http\Middleware;

use Closure;
use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface;
use CloudCreativity\JsonApi\Services\EnvironmentService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Integration\CurrentRequestInterface;
use Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
use Neomerx\JsonApi\Contracts\Parameters\ParametersFactoryInterface;

/**
 * Class InitCodecMatcher
 * @package CloudCreativity\JsonApi
 */
class BootJsonApi
{

    /**
     * @var Application
     */
    private $app;

    /**
     * @var EnvironmentService
     */
    private $env;

    /**
     * @param Application $application
     * @param EnvironmentService $environment
     */
    public function __construct(Application $application, EnvironmentService $environment)
    {
        $this->app = $application;
        $this->env = $environment;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @param string|null $codecMatcherName
     *      the name of the codec matcher that should be used, or null to use the default.
     * @return mixed
     */
    public function handle($request, Closure $next, $codecMatcherName = null)
    {
        $this->register(($codecMatcherName ?: null));

        return $next($request);
    }

    /**
     * @param string|null $name
     *      the codec matcher name that should be used, or null for the default.
     */
    private function register($name)
    {
        /** @var CodecMatcherRepositoryInterface $repository */
        $repository = $this->app->make(CodecMatcherRepositoryInterface::class);
        /** @var ExceptionThrowerInterface $exceptionThrower */
        $exceptionThrower = $this->app->make(ExceptionThrowerInterface::class);
        /** @var ParametersFactoryInterface $parametersFactory */
        $parametersFactory = $this->app->make(ParametersFactoryInterface::class);
        /** @var CurrentRequestInterface $currentRequest */
        $currentRequest = $this->app->make(CurrentRequestInterface::class);

        $codecMatcher = $repository->getCodecMatcher($name);
        $parameters = $parametersFactory
            ->createParametersParser()
            ->parse($currentRequest, $exceptionThrower);

        $parametersFactory
            ->createHeadersChecker($exceptionThrower, $codecMatcher)
            ->checkHeaders($parameters);

        $this->env
            ->registerCodecMatcher($codecMatcher)
            ->registerParameters($parameters);
    }
}
