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
use CloudCreativity\JsonApi\Contracts\Stdlib\ConfigurableInterface;
use CloudCreativity\JsonApi\Error\ExceptionThrower;
use CloudCreativity\JsonApi\Exceptions\RenderContainer;
use CloudCreativity\JsonApi\Http\Middleware\InitCodecMatcher;
use CloudCreativity\JsonApi\Integration\LaravelIntegration;
use CloudCreativity\JsonApi\Keys as C;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Neomerx\JsonApi\Contracts\Exceptions\RenderContainerInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Integration\CurrentRequestInterface;
use Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
use Neomerx\JsonApi\Contracts\Integration\NativeResponsesInterface;
use Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;
use Neomerx\JsonApi\Factories\Factory;
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
        $router->middleware(C::NAME, InitCodecMatcher::class);

        // If the whole application is set to be a Json Api, push the init middleware into the kernel.
        $key = sprintf('%s.%s', C::NAME, C::IS_GLOBAL);
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
        $container = $this->app;

        // Factory
        $container->singleton(FactoryInterface::class, Factory::class);

        // Schemas Repository
        $container->singleton(SchemasRepositoryInterface::class, SchemasRepository::class);
        $container->resolving(SchemasRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure($this->getConfig(C::SCHEMAS));
        });

        // Encoder Options Repository
        $container->singleton(EncoderOptionsRepositoryInterface::class, EncoderOptionsRepository::class);
        $container->resolving(EncoderOptionsRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure($this->getConfig(C::ENCODER_OPTIONS));
        });

        // Encoders Repository
        $container->singleton(EncodersRepositoryInterface::class, EncodersRepository::class);

        // Codec Matcher Repository
        $container->singleton(CodecMatcherRepositoryInterface::class, CodecMatcherRepository::class);
        $container->resolving(CodecMatcherRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure($this->getConfig(C::CODEC_MATCHER));
        });

        // Laravel Integration
        $container->alias(CurrentRequestInterface::class, LaravelIntegration::class);
        $container->alias(NativeResponsesInterface::class, LaravelIntegration::class);
        $container->singleton(ResponsesInterface::class, Responses::class);

        // Exception Thrower
        $container->singleton(ExceptionThrowerInterface::class, ExceptionThrower::class);

        // Exception Render Container
        $container->singleton(RenderContainerInterface::class, RenderContainer::class);
        $container->resolving(RenderContainerInterface::class, function (ConfigurableInterface $renderContainer) {
            $renderContainer->configure($this->getConfig(C::EXCEPTIONS));
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

    /**
     * @param $key
     * @return array
     */
    protected function getConfig($key)
    {
        /** @var Repository $config */
        $config = $this->app->make('config');
        $key = sprintf('%s.%s', C::NAME, $key);

        return (array) $config->get($key);
    }

}
