<?php

namespace App\Models;

use App\Enums\OrigemPlano;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'plano_anterior_id', 'plano_novo_id', 'origem', 'autor_id', 'referencia', 'observacao'])]
class PlanoHistorico extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'origem' => OrigemPlano::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function planoAnterior(): BelongsTo
    {
        return $this->belongsTo(Plano::class, 'plano_anterior_id');
    }

    public function planoNovo(): BelongsTo
    {
        return $this->belongsTo(Plano::class, 'plano_novo_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
