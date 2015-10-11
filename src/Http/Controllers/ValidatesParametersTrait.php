<?php

namespace CloudCreativity\JsonApi\Http\Controllers;

use CloudCreativity\JsonApi\Error\ThrowableError;
use Illuminate\Support\Facades\Validator;
use Neomerx\JsonApi\Contracts\Parameters\ParametersInterface;

trait ValidatesParametersTrait
{

    /**
     * @return ParametersInterface
     */
    abstract public function getParameters();

    /**
     * @param array $rules
     * @param array $messages
     * @param $errorMessage
     * @return array
     */
    public function getUnrecognizedParameters(array $rules = [], array $messages = [], $errorMessage = null)
    {
        $params = (array) $this->getParameters()->getUnrecognizedParameters();

        if (empty($rules)) {
            return $params;
        }

        /** @var \Illuminate\Contracts\Validation\Validator $validator */
        $validator = Validator::make($params, $rules, $messages);

        if ($validator->fails()) {
            $errorMessage = $errorMessage ?: 'Invalid Query Parameters';
            throw new ThrowableError($errorMessage, 400);
        }

        return $params;
    }
}
