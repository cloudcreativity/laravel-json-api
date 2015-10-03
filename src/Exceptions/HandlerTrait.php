<?php

/**
 * Copyright 2015 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\JsonApi\Exceptions;

use App;
use CloudCreativity\JsonApi\Contracts\Encoder\EncoderAwareInterface;
use CloudCreativity\JsonApi\Contracts\Integration\EnvironmentInterface;
use Exception;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Exceptions\RendererContainerInterface;

/**
 * Class HandlerTrait
 * @package CloudCreativity\JsonApi\Laravel
 */
trait HandlerTrait
{

    /**
     * @return bool
     */
    public function isJsonApi()
    {
        /** @var EnvironmentInterface $environment */
        $environment = App::make(EnvironmentInterface::class);

        return $environment->hasSchemas();
    }

    /**
     * @param $request
     * @param Exception $e
     * @return Response
     */
    public function renderJsonApi($request, Exception $e)
    {
        /** @var RendererContainerInterface $rendererContainer */
        $rendererContainer = App::make(RendererContainerInterface::class);
        /** @var EnvironmentInterface $environment */
        $environment = App::make(EnvironmentInterface::class);

        // If there is no encoder, then we bug out. The client doesn't accept a content type we can render.
        if (!$environment->hasEncoder()) {
            return new Response(null, 406);
        }

        $renderer = $rendererContainer->getRenderer(get_class($e));

        // set encoder if needed
        if ($renderer instanceof EncoderAwareInterface) {
            $renderer->setEncoder($environment->getEncoder());
        }

        // set supported extensions if there are any
        $supportedExtensions = $environment->getSupportedExtensions();

        if ($supportedExtensions) {
            $renderer->withSupportedExtensions($supportedExtensions);
        }

        return $renderer
            ->withMediaType($environment->getEncoderMediaType())
            ->render($e);
    }
}
