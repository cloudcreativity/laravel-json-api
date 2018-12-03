<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ClientJobScope implements Scope
{

    /**
     * @inheritDoc
     */
    public function apply(Builder $builder, Model $model)
    {
        $request = json_api_request();

        if ($request->getProcessType()) {
            $builder->where('resource_type', $request->getResourceType());
        }
    }

}
