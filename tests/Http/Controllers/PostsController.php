<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;
use CloudCreativity\LaravelJsonApi\Tests\JsonApi\Posts\Hydrator;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use Illuminate\Support\Collection;

class PostsController extends EloquentController
{

    /**
     * @var Collection
     */
    private $invokables;

    /**
     * PostsController constructor.
     *
     * @param Hydrator $hydrator
     */
    public function __construct(Hydrator $hydrator)
    {
        parent::__construct(new Post(), $hydrator);
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
