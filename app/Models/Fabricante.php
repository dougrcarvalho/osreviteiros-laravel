<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Observers\FabricanteObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Marca/fabricante — uma só entidade compartilhada entre os tipos de item
 * (famílias e texturas). É a fonte das taxonomias "Marca" (fonte = fabricante).
 */
#[Fillable(['nome', 'slug', 'logo_url', 'logo_path', 'descricao', 'site_url'])]
#[ObservedBy(FabricanteObserver::class)]
class Fabricante extends Model
{
    use Auditavel, HasFactory;

    public static function rotuloAuditoria(): string
    {
        return 'Fabricante';
    }

    public function familias(): HasMany
    {
        return $this->hasMany(Familia::class);
    }

    public function getInicialAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->nome, 0, 1));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
