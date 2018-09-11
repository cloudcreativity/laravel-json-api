<?php

namespace CloudCreativity\LaravelJsonApi\Exceptions;

use Illuminate\Support\Collection;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function CloudCreativity\LaravelJsonApi\json_decode;

/**
 * Class ClientException
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ClientException extends \RuntimeException
{

    /**
     * @var RequestInterface|null
     */
    private $request;

    /**
     * @var ResponseInterface|null
     */
    private $response;

    /**
     * @var array|null
     */
    private $errors;

    /**
     * ClientException constructor.
     *
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param \Exception|null $previous
     */
    public function __construct(
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $previous = null
    ) {
        parent::__construct(
            $previous ? $previous->getMessage() : 'Client encountered an error.',
            $response ? $response->getStatusCode() : 0,
            $previous
        );

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return RequestInterface|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return bool
     */
    public function hasResponse()
    {
        return !is_null($this->response);
    }

    /**
     * Get any JSON API errors that are in the response.
     *
     * @return Collection
     */
    public function getErrors()
    {
        if (!is_null($this->errors)) {
            return collect($this->errors);
        }

        try {
            $this->errors = $this->parse();
        } catch (\Exception $ex) {
            $this->errors = [];
        }

        return collect($this->errors);
    }

    /**
     * @return int|null
     */
    public function getHttpCode()
    {
        return $this->response ? $this->response->getStatusCode() : null;
    }

    /**
     * Parse JSON API errors out of the response body.
     *
     * @return array
     */
    private function parse()
    {
        if (!$this->response) {
            return [];
        }

        $body = json_decode((string) $this->response->getBody(), true);

        return isset($body['errors']) ? $body['errors'] : [];
    }
}
