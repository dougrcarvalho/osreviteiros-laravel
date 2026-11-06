<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;
use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Log de auditoria: registra o que FUNCIONÁRIOS fazem.
 *
 * Alterações feitas por clientes, importações, seeders e webhooks
 * (sem funcionário logado) não são registradas automaticamente — para
 * esses casos use registrar() com $forcar = true.
 */
class AuditoriaService
{
    /** Campos nunca gravados com valor */
    private const SENSIVEIS = ['password'];

    /** Campos que não geram auditoria (remember_token muda a cada logout) */
    private const IGNORADOS = [
        'updated_at', 'created_at', 'busca_texto', 'last_login_at', 'last_login_ip', 'remember_token',
        'downloads_count', 'email_verified_at',
    ];

    private bool $pausado = false;

    public function autor(): ?User
    {
        $user = Auth::user();

        return $user instanceof User && $user->isFuncionario() ? $user : null;
    }

    public function ativo(): bool
    {
        return ! $this->pausado && $this->autor() !== null;
    }

    /** Executa sem registrar auditoria (ex.: importação em massa) */
    public function semRegistro(Closure $callback): mixed
    {
        $anterior      = $this->pausado;
        $this->pausado = true;

        try {
            return $callback();
        } finally {
            $this->pausado = $anterior;
        }
    }

    public function registrar(string $evento, ?Model $model, string $descricao, ?array $alteracoes = null, bool $forcar = false): ?Auditoria
    {
        if (! $forcar && ! $this->ativo()) {
            return null;
        }

        return Auditoria::create([
            'autor_id'       => $this->autor()?->getKey(),
            'evento'         => $evento,
            'auditavel_type' => $model?->getMorphClass(),
            'auditavel_id'   => $model?->getKey(),
            'descricao'      => mb_substr($descricao, 0, 255),
            'alteracoes'     => $alteracoes ?: null,
            'ip'             => Request::ip(),
        ]);
    }

    /** Chamado pelo trait Auditavel nos eventos do model */
    public function registrarModelo(Model $model, string $evento): void
    {
        if (! $this->ativo()) {
            return;
        }

        $alteracoes = match ($evento) {
            'atualizado' => $this->diff($model),
            'criado'     => $this->valores($model->getAttributes()),
            default      => null,
        };

        if ($evento === 'atualizado' && ! $alteracoes) {
            return;
        }

        $verbo = ['criado' => 'criou', 'atualizado' => 'alterou', 'excluido' => 'excluiu'][$evento] ?? $evento;

        $this->registrar($evento, $model, ucfirst($verbo).' '.$this->rotulo($model), $alteracoes);
    }

    public function rotulo(Model $model): string
    {
        $tipo = method_exists($model, 'rotuloAuditoria') ? $model::rotuloAuditoria() : class_basename($model);
        $nome = $model->getAttribute('nome') ?? $model->getAttribute('name') ?? $model->getAttribute('email');

        return trim("{$tipo} #{$model->getKey()} ".($nome ? "«{$nome}»" : ''));
    }

    /** @return array<string, array{de: mixed, para: mixed}> */
    private function diff(Model $model): array
    {
        $alteracoes = [];

        foreach ($model->getChanges() as $campo => $novo) {
            if (in_array($campo, self::IGNORADOS, true)) {
                continue;
            }

            $sensivel = in_array($campo, self::SENSIVEIS, true);

            $alteracoes[$campo] = [
                'de'   => $sensivel ? '••••' : $this->escalar($model->getOriginal($campo)),
                'para' => $sensivel ? '(alterada)' : $this->escalar($novo),
            ];
        }

        return $alteracoes;
    }

    private function valores(array $atributos): array
    {
        $valores = [];

        foreach ($atributos as $campo => $valor) {
            if (in_array($campo, self::IGNORADOS, true) || $campo === 'id' || $valor === null) {
                continue;
            }

            $valores[$campo] = ['de' => null, 'para' => in_array($campo, self::SENSIVEIS, true) ? '(definida)' : $this->escalar($valor)];
        }

        return $valores;
    }

    private function escalar(mixed $valor): mixed
    {
        return match (true) {
            $valor instanceof BackedEnum         => $valor->value,
            $valor instanceof \DateTimeInterface => $valor->format('Y-m-d H:i:s'),
            is_string($valor)                    => mb_strimwidth($valor, 0, 500, '…'),
            is_scalar($valor), $valor === null   => $valor,
            default                              => json_encode($valor),
        };
    }
}
