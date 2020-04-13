<?php

namespace DummyApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{

    /**
     * @return MorphTo
     */
    public function imageable()
    {
        return $this->morphTo();
    }
}
