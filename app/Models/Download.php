<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['familia_id', 'user_id'])]
class Download extends Model
{
    public const UPDATED_AT = null;

    public function familia(): BelongsTo
    {
        return $this->belongsTo(Familia::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
