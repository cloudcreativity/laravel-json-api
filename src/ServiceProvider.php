<?php

namespace CloudCreativity\JsonApi;

use CloudCreativity\JsonApi\Contracts\Integration\EnvironmentInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Stdlib\ConfigurableInterface;
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
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Exceptions\RendererContainer;
use Neomerx\JsonApi\Factories\Factory;
use Neomerx\JsonApi\Responses\Responses;
use Request;

/**
 * Class ServiceProvider
 * @package CloudCreativity\JsonApi
 */
class ServiceProvider extends BaseServiceProvider
{

    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * @param Router $router
     * @param Kernel $kernel
     */
    public function boot(Router $router, Kernel $kernel)
    {
        // Allow publishing of config file
        $this->publishes([
            __DIR__ . '/../config/json-api.php' => config_path('json-api.php'),
        ]);

        // Add Json Api middleware to the router.
        $router->middleware('json-api', Http\Middleware\BootJsonApi::class);
        $router->middleware('json-api-ext', Http\Middleware\SupportedExt::class);

        // If the whole application is set to be a Json Api, push the init middleware into the kernel.
        $global = (bool) $this->getConfig(Config::IS_GLOBAL);

        if (true === $global && method_exists($kernel, 'pushMiddleware')) {
            $kernel->pushMiddleware(Http\Middleware\BootJsonApi::class);
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

        // Environment
        $container->singleton(EnvironmentInterface::class, Integration\LaravelEnvironment::class);

        // Factory
        $container->singleton(FactoryInterface::class, Factory::class);
        $container->singleton(ParametersFactoryInterface::class, Factory::class);
        $container->singleton(SchemaFactoryInterface::class, Factory::class);

        // Codec Matcher Repository
        $container->singleton(CodecMatcherRepositoryInterface::class, Repositories\CodecMatcherRepository::class);
        $container->resolving(CodecMatcherRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure((array) $this->getConfig(Config::CODEC_MATCHER, []));
        });

        // Schemas Repository
        $container->singleton(SchemasRepositoryInterface::class, Repositories\SchemasRepository::class);
        $container->resolving(SchemasRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure((array) $this->getConfig(Config::SCHEMAS, []));
        });

        // Laravel Integration
        $container->singleton(CurrentRequestInterface::class, Integration\LaravelIntegration::class);
        $container->singleton(NativeResponsesInterface::class, Integration\LaravelIntegration::class);
        $container->singleton(ResponsesInterface::class, Responses::class);

        // Exception Thrower
        $container->singleton(ExceptionThrowerInterface::class, Exceptions\ExceptionThrower::class);

        // Exception Render Container
        $container->singleton(RendererInterface::class, Exceptions\StandardRenderer::class);
        $container->singleton(RendererContainerInterface::class, RendererContainer::class);
        $container->resolving(RendererInterface::class, function (ConfigurableInterface $renderer) {
            $renderer->configure((array) $this->getConfig(Config::EXCEPTIONS));
        });
        $container->resolving(RendererContainerInterface::class, function (RendererContainerInterface $rendererContainer) use ($container) {
            /** @var ResponsesInterface $response */
            $responses = $container->make(ResponsesInterface::class);
            $errorRenderer = new Exceptions\ErrorRenderer($responses);
            $errorsRenderer = new Exceptions\ErrorsAwareRenderer($responses);
            $rendererContainer->registerRenderer(Error\ThrowableError::class, $errorRenderer);
            $rendererContainer->registerRenderer(Error\ErrorException::class, $errorsRenderer);
            $rendererContainer->registerRenderer(Error\MultiErrorException::class, $errorsRenderer);
        });
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
        $key = sprintf('%s.%s', Config::NAME, $key);

        return $config->get($key, $default);
    }

}
