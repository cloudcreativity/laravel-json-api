<?php

namespace CloudCreativity\JsonApi\Http\Middleware;

use CloudCreativity\JsonApi\Contracts\Config\CodecMatcherRepositoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;

/**
 * Class InitCodecMatcher
 * @package CloudCreativity\JsonApi
 */
class InitCodecMatcher
{

    /**
     * @var Application
     */
    private $application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @param Request $request
     * @param \Closure $next
     * @param string|null $codecMatcherName
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, $codecMatcherName = null)
    {
        /** @var CodecMatcherRepositoryInterface $repository */
        $repository = $this->application->make(CodecMatcherRepositoryInterface::class);
        $codecMatcher = $repository->getCodecMatcher($codecMatcherName);
        $this->application->instance(CodecMatcherInterface::class, $codecMatcher);

        return $next($request);
    }
}
