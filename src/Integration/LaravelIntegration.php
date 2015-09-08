<?php

namespace CloudCreativity\JsonApi\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Integration\CurrentRequestInterface;
use Neomerx\JsonApi\Contracts\Integration\NativeResponsesInterface;

class LaravelIntegration implements CurrentRequestInterface, NativeResponsesInterface
{

    /**
     * @var Request
     */
    private $_request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->_request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Get content.
     * @return string|null
     */
    public function getContent()
    {
        $content = $this
            ->getRequest()
            ->getContent();

        return !empty($content) ? $content : null;
    }

    /**
     * Get inputs.
     * @return array
     */
    public function getQueryParameters()
    {
        return $this
            ->getRequest()
            ->query();
    }

    /**
     * Get header value.
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader($name)
    {
        return $this
            ->getRequest()
            ->header($name, null);
    }

    /**
     * Create HTTP response.
     *
     * @param string|null $content
     * @param int $statusCode
     * @param array $headers
     *
     * @return mixed
     */
    public function createResponse($content, $statusCode, array $headers)
    {
        return new Response($content, $statusCode, $headers);
    }
}
