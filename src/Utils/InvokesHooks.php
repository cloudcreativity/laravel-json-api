<?php

namespace CloudCreativity\LaravelJsonApi\Utils;

/**
 * Trait InvokesHooks
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait InvokesHooks
{

    /**
     * Should the invoked result be returned?
     *
     * @param mixed $result
     * @return bool
     */
    abstract protected function isInvokedResult($result): bool;

    /**
     * Invoke a hook.
     *
     * @param string $hook
     * @param mixed ...$arguments
     * @return mixed|null
     */
    protected function invoke(string $hook, ...$arguments)
    {
        if (!method_exists($this, $hook)) {
            return null;
        }

        $result = $this->{$hook}(...$arguments);

        return $this->isInvokedResult($result) ? $result : null;
    }

    /**
     * Invoke multiple hooks.
     *
     * @param iterable $hooks
     * @param mixed ...$arguments
     * @return mixed|null
     */
    protected function invokeMany(iterable $hooks, ...$arguments)
    {
        foreach ($hooks as $hook) {
            $result = $this->invoke($hook, ...$arguments);

            if (!is_null($result)) {
                return $result;
            }
        }

        return null;
    }

}
