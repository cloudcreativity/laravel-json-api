<?php

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorProviderInterface;

/**
 * Class FetchResource
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class FetchResource extends ValidatedRequest
{

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!$authorizer = $this->getAuthorizer()) {
            return;
        }

        $authorizer->read($this->getRecord(), $this->request);
    }

    /**
     * @inheritDoc
     */
    protected function validateQuery()
    {
        if (!$validators = $this->getValidators()) {
            return;
        }

        /** Pre-1.0 validators */
        if ($validators instanceof ValidatorProviderInterface) {
            $validators->resourceQueryChecker()->checkQuery($this->getEncodingParameters());
            return;
        }

        /** 1.0 validators */
        $this->passes(
            $validators->fetchQuery($this->query())
        );
    }

}
