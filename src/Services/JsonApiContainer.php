<?php

namespace CloudCreativity\JsonApi\Services;

use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use RuntimeException;

class JsonApiContainer
{

    /**
     * @var CodecMatcherInterface
     */
    private $codecMatcher;

    /**
     * @var ContainerInterface
     */
    private $schemaContainer;

    /**
     * @var string|null
     */
    private $urlPrefix;

    /**
     * @var SupportedExtensionsInterface|null
     */
    private $supportExt;

    /**
     * @var EncodingParametersInterface|null
     */
    private $encodingParams;

    /**
     * EnvironmentService constructor.
     * @param CodecMatcherInterface $codecMatcher
     * @param ContainerInterface $schemaContainer
     * @param null $urlPrefix
     */
    public function __construct(
        CodecMatcherInterface $codecMatcher,
        ContainerInterface $schemaContainer,
        $urlPrefix = null
    ) {
        $this->codecMatcher = $codecMatcher;
        $this->schemaContainer = $schemaContainer;
        $this->urlPrefix = $urlPrefix;
    }

    /**
     * @param SupportedExtensionsInterface $extensions
     */
    public function registerSupportedExtensions(SupportedExtensionsInterface $extensions)
    {
        $this->supportExt = $extensions;
    }

    /**
     * @param EncodingParametersInterface $encodingParams
     */
    public function registerEncodingParameters(EncodingParametersInterface $encodingParams)
    {
        $this->encodingParams = $encodingParams;
    }

    /**
     * @return CodecMatcherInterface
     */
    public function getCodecMatcher()
    {
        return $this->codecMatcher;
    }

    /**
     * @return ContainerInterface
     */
    public function getSchemaContainer()
    {
        return $this->schemaContainer;
    }

    /**
     * @return null|string
     */
    public function getUrlPrefix()
    {
        return $this->urlPrefix;
    }

    /**
     * @return EncoderInterface
     */
    public function getEncoder()
    {
        $encoder = $this
            ->getCodecMatcher()
            ->getEncoder();

        if (!$encoder instanceof EncoderInterface) {
            throw new RuntimeException('No Json-Api encoder matched. An error should have been sent to the client.');
        }

        return $encoder;
    }

    /**
     * @return SupportedExtensionsInterface|null
     */
    public function getSupportExtensions()
    {
        return $this->supportExt;
    }

    /**
     * @return EncodingParametersInterface|null
     */
    public function getEncodingParameters()
    {
        return $this->encodingParams;
    }
}
