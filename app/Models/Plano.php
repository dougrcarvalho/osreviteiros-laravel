<?php

namespace App\Models;

use App\Enums\TipoItem;
use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'slug', 'wp_role', 'pago', 'checkout_url', 'ordem'])]
class Plano extends Model
{
    use Auditavel, HasFactory;

    public static function rotuloAuditoria(): string
    {
        return 'Plano';
    }

    public const GRATIS = 'gratis';

    public const PREMIUM = 'premium';

    /** Descontinuado: assinantes antigos continuam com o acesso do Basic */
    public const PLUS = 'plus';

    protected function casts(): array
    {
        return [
            'pago' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeOrdenados(Builder $q): Builder
    {
        return $q->orderBy('ordem');
    }

    public function isPago(): bool
    {
        return $this->pago;
    }

    /** Basic (e Plus): famílias e texturas. Premium: também projetos. Grátis: só itens gratuitos. */
    public function liberaTipo(TipoItem $tipo): bool
    {
        return match ($tipo) {
            TipoItem::Projeto => $this->slug === self::PREMIUM,
            default           => $this->isPago(),
        };
    }
}
