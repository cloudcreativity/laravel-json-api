<?php

namespace App\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use Illuminate\Support\Collection;

class PostsController extends JsonApiController
{

    /**
     * @var Collection
     */
    private $invokables;

    public function __construct()
    {
        $this->invokables = collect();
    }

    /**
     * Set a callback for testing purposes.
     *
     * @param $event
     * @param callable $fn
     * @return $this
     */
    public function on($event, callable $fn)
    {
        $this->invokables[$event] = $fn;

        return $this;
    }

    /**
     * @param array ...$args
     */
    protected function saving(...$args)
    {
        $this->invoke('saving', $args);
    }

    /**
     * Invoke a test callback.
     *
     * @param $event
     * @param array $args
     */
    private function invoke($event, array $args)
    {
        if ($fn = $this->invokables->get($event)) {
            call_user_func_array($fn, $args);
        }
    }

}
