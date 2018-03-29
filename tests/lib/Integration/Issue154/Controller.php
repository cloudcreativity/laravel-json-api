<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue154;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use Illuminate\Http\Response;

class Controller extends JsonApiController
{

    /**
     * @var array
     */
    public $responses = [];

    /**
     * @var array
     */
    public $unexpected = [];

    /**
     * @return Response|null
     */
    public function saving()
    {
        return $this->response('saving');
    }

    /**
     * @return Response|null
     */
    public function creating()
    {
        return $this->response('creating');
    }

    /**
     * @return Response|null
     */
    public function updating()
    {
        return $this->response('updating');
    }

    /**
     * @return Response|null
     */
    public function saved()
    {
        return $this->response('saved');
    }

    /**
     * @return Response|null
     */
    public function created()
    {
        return $this->response('created');
    }

    /**
     * @return Response|null
     */
    public function updated()
    {
        return $this->response('updated');
    }

    /**
     * @return Response|null
     */
    public function deleting()
    {
        return $this->response('deleting');
    }

    /**
     * @return Response|null
     */
    public function deleted()
    {
        return $this->response('deleted');
    }

    /**
     * @param $name
     * @return Response|null
     */
    private function response($name)
    {
        if (in_array($name, $this->unexpected, true)) {
            throw new \RuntimeException("Not expecting {$name} to be invoked.");
        }

        return isset($this->responses[$name]) ? $this->responses[$name] : null;
    }
}
