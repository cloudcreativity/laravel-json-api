<?php

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SubstituteBindings
{

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($record = json_api_request()->getRecord()) {
            $request->route()->setParameter('resource', $record);
        }

        return $next($request);
    }
}
