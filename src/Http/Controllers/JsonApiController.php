<?php

namespace CloudCreativity\JsonApi\Http\Controllers;

use CloudCreativity\JsonApi\Error\ErrorException;
use CloudCreativity\JsonApi\Error\ThrowableError;
use Illuminate\Routing\Controller;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

/**
 * Class JsonApiController
 * @package CloudCreativity\JsonApi
 */
class JsonApiController extends Controller
{

    use QueryCheckerTrait,
        DocumentValidatorTrait;

    /**
     * Whether query parameters should automatically be checked before the controller action method is invoked.
     *
     * @var bool
     */
    protected $autoCheckQueryParameters = true;

    /**
     * @param string $method
     * @param array $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        if (true === $this->autoCheckQueryParameters) {
            $this->checkParameters();
        }

        return parent::callAction($method, $parameters);
    }

    /**
     * @param array $parameters
     * @return void
     * @throws ErrorInterface
     */
    public function missingMethod($parameters = [])
    {
        throw new ThrowableError('Method Not Allowed', 405);
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return void
     * @throws ErrorException
     */
    public function __call($method, $parameters)
    {
        throw new ThrowableError('Not Implemented', 501);
    }
}
