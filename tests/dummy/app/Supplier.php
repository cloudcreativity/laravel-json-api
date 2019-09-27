<?php

namespace DummyApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Supplier extends Model
{

    /**
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * @return HasOneThrough
     */
    public function userHistory(): HasOneThrough
    {
        return $this->hasOneThrough(History::class, User::class);
    }
}
