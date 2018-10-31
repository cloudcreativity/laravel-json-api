<?php

namespace DummyApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avatar extends Model
{

    /**
     * @var array
     */
    protected $fillable = [
        'media_type',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
