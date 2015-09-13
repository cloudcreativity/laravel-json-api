<?php

namespace CloudCreativity\JsonApi;

use CloudCreativity\JsonApi\Config as C;
use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\DecodersRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\EncoderOptionsRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\EncodersRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Stdlib\ConfigurableInterface;
use CloudCreativity\JsonApi\Contracts\Stdlib\MutableConfigInterface;
use CloudCreativity\JsonApi\Error\ErrorException;
use CloudCreativity\JsonApi\Error\MultiErrorException;
use CloudCreativity\JsonApi\Error\ThrowableError;
use CloudCreativity\JsonApi\Exceptions\ErrorRenderer;
use CloudCreativity\JsonApi\Exceptions\ErrorsAwareRenderer;
use CloudCreativity\JsonApi\Exceptions\ExceptionThrower;
use CloudCreativity\JsonApi\Exceptions\StandardRenderer;
use CloudCreativity\JsonApi\Http\Middleware\BootJsonApi;
use CloudCreativity\JsonApi\Http\Middleware\SupportedExt;
use CloudCreativity\JsonApi\Integration\LaravelIntegration;
use CloudCreativity\JsonApi\Middleware as M;
use CloudCreativity\JsonApi\Repositories\CodecMatcherRepository;
use CloudCreativity\JsonApi\Repositories\DecodersRepository;
use CloudCreativity\JsonApi\Repositories\EncoderOptionsRepository;
use CloudCreativity\JsonApi\Repositories\EncodersRepository;
use CloudCreativity\JsonApi\Repositories\SchemasRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Neomerx\JsonApi\Contracts\Exceptions\RendererContainerInterface;
use Neomerx\JsonApi\Contracts\Exceptions\RendererInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Integration\CurrentRequestInterface;
use Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
use Neomerx\JsonApi\Contracts\Integration\NativeResponsesInterface;
use Neomerx\JsonApi\Contracts\Parameters\ParametersFactoryInterface;
use Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;
use Neomerx\JsonApi\Exceptions\RendererContainer;
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
     * @param Kernel $kernel
     */
    public function boot(Router $router, Kernel $kernel)
    {
        // Allow publishing of config file
        $this->publishes(__DIR__ . '/config/json-api.php', config_path('json-api.php'));

        // Add Json Api middleware to the router.
        $router->middleware(M::JSON_API, BootJsonApi::class);
        $router->middleware(M::SUPPORTED_EXT, SupportedExt::class);

        // If the whole application is set to be a Json Api, push the init middleware into the kernel.
        $global = (bool) $this->getConfig(C::IS_GLOBAL);

        if (true === $global && method_exists($kernel, 'pushMiddleware')) {
            $kernel->pushMiddleware(BootJsonApi::class);
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
        $container->alias(ParametersFactoryInterface::class, FactoryInterface::class);
        $container->singleton(FactoryInterface::class, Factory::class);

        // Encoders Repository
        $container->singleton(EncodersRepositoryInterface::class, EncodersRepository::class);
        $container->singleton(SchemasRepositoryInterface::class, SchemasRepository::class);
        $container->singleton(EncoderOptionsRepositoryInterface::class, EncoderOptionsRepository::class);
        $container->resolving(EncodersRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure($this->getConfig(C::ENCODERS, []));
        });
        $container->resolving(EncoderOptionsRepositoryInterface::class, function (EncoderOptionsRepositoryInterface $repository) {
            $repository->addModifier(function (MutableConfigInterface $config) {
                $key = EncoderOptionsRepositoryInterface::URL_PREFIX;
                if (!$config->has($key)) {
                    $config->set($key, \Request::getSchemeAndHttpHost());
                };
            });
        });

        // Decoders Repository
        $container->singleton(DecodersRepositoryInterface::class, DecodersRepository::class);
        $container->resolving(DecodersRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure($this->getConfig(C::DECODERS, []));
        });

        // Codec Matcher Repository
        $container->singleton(CodecMatcherRepositoryInterface::class, CodecMatcherRepository::class);
        $container->resolving(CodecMatcherRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure($this->getConfig(C::CODEC_MATCHER, []));
        });

        // Laravel Integration
        $container->alias(CurrentRequestInterface::class, LaravelIntegration::class);
        $container->alias(NativeResponsesInterface::class, LaravelIntegration::class);
        $container->singleton(ResponsesInterface::class, Responses::class);

        // Exception Thrower
        $container->singleton(ExceptionThrowerInterface::class, ExceptionThrower::class);

        // Exception Render Container
        $container->singleton(RendererInterface::class, StandardRenderer::class);
        $container->singleton(RendererContainerInterface::class, RendererContainer::class);
        $container->resolving(RendererInterface::class, function (ConfigurableInterface $renderer) {
            $renderer->configure($this->getConfig(C::EXCEPTIONS));
        });
        $container->resolving(RendererContainerInterface::class, function (RendererContainerInterface $rendererContainer) use ($container) {
            /** @var ResponsesInterface $response */
            $responses = $container->make(ResponsesInterface::class);
            $errorRenderer = new ErrorRenderer($responses);
            $errorsRenderer = new ErrorsAwareRenderer($responses);
            $rendererContainer->registerRenderer(ThrowableError::class, $errorRenderer);
            $rendererContainer->registerRenderer(ErrorException::class, $errorsRenderer);
            $rendererContainer->registerRenderer(MultiErrorException::class, $errorsRenderer);
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return [
            ParametersFactoryInterface::class,
            FactoryInterface::class,
            SchemasRepositoryInterface::class,
            EncoderOptionsRepositoryInterface::class,
            EncodersRepositoryInterface::class,
            DecodersRepositoryInterface::class,
            CodecMatcherRepositoryInterface::class,
            CurrentRequestInterface::class,
            NativeResponsesInterface::class,
            ResponsesInterface::class,
            ExceptionThrowerInterface::class,
            RendererInterface::class,
            RendererContainerInterface::class,
        ];
    }

    /**
     * @param $key
     * @param $default
     * @return array
     */
    protected function getConfig($key, $default = null)
    {
        /** @var Repository $config */
        $config = $this->app->make('config');
        $key = sprintf('%s.%s', C::NAME, $key);

        return (array) $config->get($key, $default);
    }

}
