<?php

namespace CloudCreativity\JsonApi\Exceptions;

use App;
use CloudCreativity\JsonApi\Contracts\Encoder\EncoderAwareInterface;
use Exception;
use Illuminate\Http\Response;
use JsonApi;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Exceptions\RendererContainerInterface;
use Neomerx\JsonApi\Contracts\Parameters\SupportedExtensionsInterface;

trait HandlerTrait
{

    /**
     * @return bool
     */
    public function isJsonApi()
    {
        return JsonApi::isActive();
    }

    /**
     * @param $request
     * @param Exception $e
     * @return Response
     */
    public function renderJsonApi($request, Exception $e)
    {
        /** @var RendererContainerInterface $renderContainer */
        $renderContainer = App::make(RendererContainerInterface::class);
        /** @var CodecMatcherInterface $codecMatcher */
        $codecMatcher = JsonApi::getCodecMatcher();

        // If there is no encoder, then we bug out. The client doesn't accept a content type we can render.
        if (!$codecMatcher->getEncoder()) {
            return new Response(null, 406);
        }

        $renderer = $renderContainer->getRenderer(get_class($e));

        if ($renderer instanceof EncoderAwareInterface) {
            $renderer->setEncoder($codecMatcher->getEncoder());
        }

        /** @var SupportedExtensionsInterface|null $ext */
        $supportedExtensions = JsonApi::getSupportedExtensions();

        if ($supportedExtensions) {
            $renderer->withSupportedExtensions($supportedExtensions);
        }

        $contentType = $codecMatcher->getEncoderHeaderMatchedType();

        if (!$contentType) {
            $contentType = $codecMatcher->getEncoderRegisteredMatchedType();
        }

        return $renderer
            ->withMediaType($contentType)
            ->render($e);
    }
}
