<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Funcao;
use App\Enums\StatusConta;
use App\Enums\TipoItem;
use App\Models\Concerns\Auditavel;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'plano_id', 'wp_user_id', 'funcao', 'status', 'bloqueio_motivo', 'bloqueado_em'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use Auditavel, HasApiTokens, HasFactory, Notifiable;

    protected $attributes = [
        'status' => 'ativo',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'funcao'            => Funcao::class,
            'status'            => StatusConta::class,
            'bloqueado_em'      => 'datetime',
            'last_login_at'     => 'datetime',
        ];
    }

    public static function rotuloAuditoria(): string
    {
        return 'Usuário';
    }

    // ─── Relacionamentos ────────────────────────────────────────
    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    /** Itens favoritados (pivot `favoritos`) */
    public function favoritos(): BelongsToMany
    {
        return $this->belongsToMany(Familia::class, 'favoritos')->withTimestamps()->withPivot('id');
    }

    public function cancelamentos(): HasMany
    {
        return $this->hasMany(SolicitacaoCancelamento::class);
    }

    public function planoHistoricos(): HasMany
    {
        return $this->hasMany(PlanoHistorico::class)->latest('id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────
    public function scopeClientes(Builder $q): Builder
    {
        return $q->whereNull('funcao');
    }

    public function scopeFuncionarios(Builder $q): Builder
    {
        return $q->whereNotNull('funcao');
    }

    // ─── Helpers de plano ───────────────────────────────────────

    /** Tem plano pago (Basic / Plus / Premium) */
    public function hasPlano(): bool
    {
        return $this->plano?->isPago() ?? false;
    }

    /** O plano libera os itens (não gratuitos) desse tipo? Ver Plano::liberaTipo */
    public function planoLibera(TipoItem $tipo): bool
    {
        return $this->plano?->liberaTipo($tipo) ?? false;
    }

    /** Sem plano ou plano grátis — só baixa famílias "acessar-gratis" */
    public function hasPlanoGratis(): bool
    {
        return ! $this->hasPlano();
    }

    // ─── Conta / funcionários ───────────────────────────────────
    public function isFuncionario(): bool
    {
        return $this->funcao !== null;
    }

    public function isAdministrador(): bool
    {
        return $this->funcao === Funcao::Administrador;
    }

    public function isBloqueado(): bool
    {
        return $this->status === StatusConta::Bloqueado;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isFuncionario() && ! $this->isBloqueado();
    }
}
