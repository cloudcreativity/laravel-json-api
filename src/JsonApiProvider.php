<?php

namespace CloudCreativity\JsonApi;

use CloudCreativity\JsonApi\Config\CodecMatcherRepository;
use CloudCreativity\JsonApi\Config\EncoderOptionsRepository;
use CloudCreativity\JsonApi\Config\EncodersRepository;
use CloudCreativity\JsonApi\Config\SchemasRepository;
use CloudCreativity\JsonApi\Contracts\Config\CodecMatcherRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Config\EncoderOptionsRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Config\EncodersRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Config\SchemasRepositoryInterface;
use CloudCreativity\JsonApi\Exceptions\RenderContainer;
use CloudCreativity\JsonApi\Integration\LaravelIntegration;
use CloudCreativity\JsonApi\Error\ExceptionThrower;
use CloudCreativity\JsonApi\Config\Config as C;
use CloudCreativity\JsonApi\Http\Middleware\Middleware as M;
use CloudCreativity\JsonApi\Http\Middleware\InitCodecMatcher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Factories\Factory;
use Neomerx\JsonApi\Contracts\Integration\CurrentRequestInterface;
use Neomerx\JsonApi\Contracts\Integration\NativeResponsesInterface;
use Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;
use Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
use Neomerx\JsonApi\Contracts\Exceptions\RenderContainerInterface;
use Neomerx\JsonApi\Responses\Responses;

/**
 * Class ServiceProvider
 * @package CloudCreativity\JsonApi
 */
class ServiceProvider extends BaseServiceProvider
{

    /**
     * @var bool
     */
    protected $defer = true;

    /**
     * @param Router $router
     * @param Repository $repository
     * @param Kernel $kernel
     */
    public function boot(Router $router, Repository $repository, Kernel $kernel)
    {
        // Add Json Api middleware to the router.
        $router->middleware(M::JSON_API, InitCodecMatcher::class);

        // If the whole application is set to be a Json Api, push the init middleware into the kernel.
        $key = sprintf('%s.%s', C::KEY, C::IS_GLOBAL);
        $global = $repository->get($key, false);

        if (true === $global && method_exists($kernel, 'pushMiddleware')) {
            $kernel->pushMiddleware(InitCodecMatcher::class);
        }
    }

    /**
     * Register JSON API services.
     *
     * @return void
     */
    public function register()
    {
        // Factory
        $this->app->singleton(FactoryInterface::class, Factory::class);

        // Schemas Repository
        $this->app->singleton(SchemasRepositoryInterface::class, function (Container $container) {
            /** @var Repository $config */
            $config = $container->make('config');
            $key = sprintf('%s.%s', C::KEY, C::SCHEMAS);
            return new SchemasRepository((array) $config->get($key));
        });

        // Encoder Options Repository
        $this->app->singleton(EncoderOptionsRepositoryInterface::class, function (Container $container) {
            /** @var Repository $config */
            $config = $container->make('config');
            $key = sprintf('%s.%s', C::KEY, C::ENCODER_OPTIONS);
            return new EncoderOptionsRepository((array) $config->get($key));
        });

        // Encoders Repository
        $this->app->singleton(EncodersRepositoryInterface::class, EncodersRepository::class);

        // Codec Matcher Repository
        $this->app->singleton(CodecMatcherRepositoryInterface::class, CodecMatcherRepository::class);

        // Laravel Integration
        $this->app->alias(CurrentRequestInterface::class, LaravelIntegration::class);
        $this->app->alias(NativeResponsesInterface::class, LaravelIntegration::class);
        $this->app->singleton(ResponsesInterface::class, Responses::class);

        // Exception Thrower
        $this->app->singleton(ExceptionThrowerInterface::class, ExceptionThrower::class);

        // Exception Render Container
        $this->app->singleton(RenderContainerInterface::class, function (Container $container) {
            /** @var Repository $config */
            $config = $container->make('config');
            $key = sprintf('%s.%s', C::KEY, C::EXCEPTION_RENDER_CONTAINER);
            $renderContainer = new RenderContainer();
            $renderContainer->configure((array) $config->get($key));
            return $renderContainer;
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return [
            FactoryInterface::class,
            SchemasRepositoryInterface::class,
            EncoderOptionsRepositoryInterface::class,
            EncodersRepositoryInterface::class,
            CodecMatcherRepositoryInterface::class,
            CurrentRequestInterface::class,
            NativeResponsesInterface::class,
            ResponsesInterface::class,
            RenderContainerInterface::class,
        ];
    }
}
