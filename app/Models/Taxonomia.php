<?php

namespace App\Models;

use App\Enums\FonteTaxonomia;
use App\Enums\TipoItem;
use App\Models\Concerns\Auditavel;
use App\Observers\TaxonomiaObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma forma de classificar itens de um tipo (Categoria, Subcategoria, Marca,
 * Cor, Tipo de projeto…). Configurável no painel: para criar uma nova
 * ("Cor" para famílias) basta cadastrar aqui — filtros, busca, formulário
 * do item e importação passam a enxergá-la sem mudança de código.
 *
 * O `slug` é o parâmetro de URL do filtro (/familias?cor=azul).
 */
#[Fillable(['tipo_item', 'slug', 'nome', 'nome_plural', 'pai_id', 'fonte', 'ordem', 'ativa', 'wp_taxonomia'])]
#[ObservedBy(TaxonomiaObserver::class)]
class Taxonomia extends Model
{
    use Auditavel, HasFactory;

    /** Chaves de URL com significado próprio — não podem ser slug de taxonomia */
    public const RESERVADOS = ['biblioteca', 'busca', 'orderby', 'gratis', 'page', 'per_page', 'nivel', 'taxonomia'];

    /** Slug obrigatório da taxonomia cuja fonte é o fabricante */
    public const SLUG_MARCA = 'marca';

    public static function rotuloAuditoria(): string
    {
        return 'Taxonomia';
    }

    protected function casts(): array
    {
        return [
            'tipo_item' => TipoItem::class,
            'fonte'     => FonteTaxonomia::class,
            'ativa'     => 'boolean',
            'ordem'     => 'integer',
        ];
    }

    // ─── Relacionamentos ────────────────────────────────────────
    public function pai(): BelongsTo
    {
        return $this->belongsTo(Taxonomia::class, 'pai_id');
    }

    public function filhas(): HasMany
    {
        return $this->hasMany(Taxonomia::class, 'pai_id');
    }

    public function termos(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────
    public function scopeDoTipo(Builder $q, TipoItem|string $tipo): Builder
    {
        return $q->where('tipo_item', $tipo instanceof TipoItem ? $tipo->value : $tipo);
    }

    public function scopeAtivas(Builder $q): Builder
    {
        return $q->where('ativa', true);
    }

    public function scopeOrdenadas(Builder $q): Builder
    {
        return $q->orderBy('ordem')->orderBy('id');
    }

    /** Só as de termos próprios (as de fabricante usam a tabela fabricantes) */
    public function scopeDeTermos(Builder $q): Builder
    {
        return $q->where('fonte', FonteTaxonomia::Termos->value);
    }

    // ─── Helpers ────────────────────────────────────────────────
    public function usaFabricante(): bool
    {
        return $this->fonte === FonteTaxonomia::Fabricante;
    }

    /** "Famílias › Categoria" */
    public function rotuloCompleto(): string
    {
        return $this->tipo_item->getLabel().' › '.$this->nome;
    }
}
