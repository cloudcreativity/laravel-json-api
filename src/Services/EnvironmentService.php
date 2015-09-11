<?php

namespace CloudCreativity\JsonApi\Services;

use CloudCreativity\JsonApi\Routing\ResourceRegistrar;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Parameters\ParametersInterface;
use Neomerx\JsonApi\Contracts\Parameters\SupportedExtensionsInterface;
use RuntimeException;

class EnvironmentService
{

    /**
     * @var ResourceRegistrar
     */
    private $registrar;

    /**
     * @var CodecMatcherInterface|null
     */
    private $codecMatcher;

    /**
     * @var ParametersInterface|null
     */
    private $parameters;

    /**
     * @var SupportedExtensionsInterface|null
     */
    private $supportedExtensions;

    /**
     * @param ResourceRegistrar $registrar
     */
    public function __construct(ResourceRegistrar $registrar)
    {
        $this->registrar = $registrar;
    }

    /**
     * @param $resourceType
     * @param $controllerName
     * @param array $options
     * @return ResourceRegistrar
     */
    public function resource($resourceType, $controllerName, array $options = [])
    {
        $this->registrar->resource($resourceType, $controllerName, $options);

        return $this->registrar;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->codecMatcher instanceof CodecMatcherInterface;
    }

    /**
     * @param CodecMatcherInterface $codecMatcher
     * @return $this
     */
    public function registerCodecMatcher(CodecMatcherInterface $codecMatcher)
    {
        $this->codecMatcher = $codecMatcher;

        return $this;
    }

    /**
     * @return CodecMatcherInterface
     */
    public function getCodecMatcher()
    {
        if (!$this->codecMatcher instanceof CodecMatcherInterface) {
            throw new RuntimeException('No registered codec matcher: are you in a JSON API route?');
        }

        return $this->codecMatcher;
    }

    /**
     * @param ParametersInterface $parameters
     * @return $this
     */
    public function registerParameters(ParametersInterface $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return ParametersInterface
     */
    public function getParameters()
    {
        if (!$this->parameters instanceof ParametersInterface) {
            throw new RuntimeException('No registered parameters: are you in a JSON API route?');
        }

        return $this->parameters;
    }

    /**
     * @param SupportedExtensionsInterface $extensions
     * @return $this
     */
    public function registerSupportedExtensions(SupportedExtensionsInterface $extensions)
    {
        $this->supportedExtensions = $extensions;

        return $this;
    }

    /**
     * @return SupportedExtensionsInterface|null
     */
    public function getSupportedExtensions()
    {
        return $this->supportedExtensions;
    }
}
