<?php

namespace CloudCreativity\JsonApi\Http\Middleware;

use CloudCreativity\JsonApi\Contracts\Config\CodecMatcherRepositoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;

class InitCodecMatcher
{

    /**
     * @var Application
     */
    private $_application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->_application = $application;
    }

    /**
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, $codecMatcherName = null)
    {
        /** @var CodecMatcherRepositoryInterface $repository */
        $repository = $this->_application->make(CodecMatcherRepositoryInterface::class);
        $codecMatcher = $repository->get($codecMatcherName ?: CodecMatcherRepositoryInterface::DEFAULTS);
        $this->_application->instance(CodecMatcherInterface::class, $codecMatcher);

        return $next($request);
    }
}
