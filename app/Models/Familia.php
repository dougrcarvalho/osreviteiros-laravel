<?php

namespace App\Models;

use App\Enums\TipoItem;
use App\Models\Concerns\Auditavel;
use App\Observers\FamiliaObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tipo', 'nome', 'slug', 'descricao', 'imagem_url', 'gratuita',
    'link_download', 'curtidas', 'fabricante_id', 'ativo', 'wp_post_id',
    'imagem_path', 'arquivo_disk', 'arquivo_path', 'arquivo_nome', 'arquivo_tamanho',
])]
// link_download nunca sai em toArray()/toJson() — só via DownloadService
#[Hidden(['link_download', 'busca_texto'])]
#[ObservedBy(FamiliaObserver::class)]
class Familia extends Model
{
    use Auditavel, HasFactory;

    public static function rotuloAuditoria(): string
    {
        return 'Família';
    }

    protected function casts(): array
    {
        return [
            'tipo'     => TipoItem::class,
            'ativo'    => 'boolean',
            'gratuita' => 'boolean',
            'curtidas' => 'integer',
            'downloads_count' => 'integer',
            'arquivo_tamanho' => 'integer',
        ];
    }

    // ─── Relacionamentos ────────────────────────────────────────
    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'familia_categoria');
    }

    public function fabricante(): BelongsTo
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────
    public function scopeAtivo(Builder $q): Builder
    {
        return $q->where('ativo', true);
    }

    public function scopeTipo(Builder $q, TipoItem|string $tipo): Builder
    {
        return $q->where('tipo', $tipo instanceof TipoItem ? $tipo->value : $tipo);
    }

    public function scopeComTermo(Builder $q, int $taxonomiaId, string $slug): Builder
    {
        return $q->whereHas('categorias', fn (Builder $c) => $c
            ->where('taxonomia_id', $taxonomiaId)
            ->where('slug', $slug));
    }

    // ─── Helpers de acesso ──────────────────────────────────────
    /** Liberada no plano grátis (antes: termo "acessar-gratis") */
    public function isGratuita(): bool
    {
        return (bool) $this->gratuita;
    }

    public function urlPublica(): string
    {
        return route($this->tipo->rotaShow(), $this);
    }

    /** Arquivo enviado pelo painel (servido pela rota protegida) em vez de link externo */
    public function temArquivoLocal(): bool
    {
        return $this->arquivo_path !== null;
    }

    public function getIsNovoAttribute(): bool
    {
        return $this->created_at?->gt(now()->subDays(30)) ?? false;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
