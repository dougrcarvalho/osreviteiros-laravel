<?php

namespace App\Models\Concerns;

use App\Services\AuditoriaService;

/** Registra criação/alteração/exclusão feitas por funcionários (ver AuditoriaService) */
trait Auditavel
{
    public static function bootAuditavel(): void
    {
        static::created(fn ($model) => app(AuditoriaService::class)->registrarModelo($model, 'criado'));
        static::updated(fn ($model) => app(AuditoriaService::class)->registrarModelo($model, 'atualizado'));
        static::deleted(fn ($model) => app(AuditoriaService::class)->registrarModelo($model, 'excluido'));
    }
}
