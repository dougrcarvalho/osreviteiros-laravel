<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'plano_id', 'motivo', 'atendida_em'])]
class SolicitacaoCancelamento extends Model
{
    protected $table = 'solicitacoes_cancelamento';

    protected function casts(): array
    {
        return ['atendida_em' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function scopePendentes(Builder $q): Builder
    {
        return $q->whereNull('atendida_em');
    }
}
