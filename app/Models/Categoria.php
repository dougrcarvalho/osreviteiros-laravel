<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Observers\CategoriaObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Termo de uma taxonomia (ex.: "Automóveis" em Famílias › Categoria).
 * Slug único dentro da taxonomia.
 */
#[Fillable(['taxonomia_id', 'nome', 'slug', 'parent_id', 'ordem', 'wp_term_id'])]
#[ObservedBy(CategoriaObserver::class)]
class Categoria extends Model
{
    use Auditavel, HasFactory;

    public static function rotuloAuditoria(): string
    {
        return 'Termo';
    }

    // ─── Relacionamentos ────────────────────────────────────────
    public function taxonomia(): BelongsTo
    {
        return $this->belongsTo(Taxonomia::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    public function filhos(): HasMany
    {
        return $this->hasMany(Categoria::class, 'parent_id')->orderBy('nome');
    }

    public function familias(): BelongsToMany
    {
        return $this->belongsToMany(Familia::class, 'familia_categoria');
    }

    // ─── Scopes ─────────────────────────────────────────────────
    public function scopeDaTaxonomia(Builder $q, Taxonomia|int $taxonomia): Builder
    {
        return $q->where('taxonomia_id', $taxonomia instanceof Taxonomia ? $taxonomia->id : $taxonomia);
    }

    public function scopeOrdenadas(Builder $q): Builder
    {
        return $q->orderBy('ordem')->orderBy('nome');
    }
}
