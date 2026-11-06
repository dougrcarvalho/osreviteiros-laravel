<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['autor_id', 'evento', 'auditavel_type', 'auditavel_id', 'descricao', 'alteracoes', 'ip'])]
class Auditoria extends Model
{
    public const UPDATED_AT = null;

    public const EVENTOS = [
        'criado'           => 'Criação',
        'atualizado'       => 'Alteração',
        'excluido'         => 'Exclusão',
        'plano_alterado'   => 'Plano alterado',
        'conta_bloqueada'  => 'Conta bloqueada',
        'conta_ativada'    => 'Conta reativada',
        'senha_alterada'   => 'Senha alterada',
        'reset_enviado'    => 'Link de senha enviado',
        'categorias'       => 'Categorias alteradas',
    ];

    protected function casts(): array
    {
        return [
            'alteracoes' => 'array',
        ];
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function auditavel(): MorphTo
    {
        return $this->morphTo();
    }
}
