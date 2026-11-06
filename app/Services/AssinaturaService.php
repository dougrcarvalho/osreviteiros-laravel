<?php

namespace App\Services;

use App\Enums\OrigemPlano;
use App\Models\Plano;
use App\Models\PlanoHistorico;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Única forma de trocar o plano de um usuário: atualiza users.plano_id
 * e grava o histórico. Usado pelo cadastro, painel, webhooks e importação.
 */
class AssinaturaService
{
    public function __construct(private AuditoriaService $auditoria) {}

    public function alterarPlano(
        User $user,
        ?Plano $plano,
        OrigemPlano $origem,
        ?string $observacao = null,
        ?string $referencia = null,
    ): ?PlanoHistorico {
        $anteriorId = $user->plano_id;

        if ($anteriorId === $plano?->id && $origem !== OrigemPlano::Cadastro) {
            return null;
        }

        return DB::transaction(function () use ($user, $plano, $origem, $observacao, $referencia, $anteriorId) {
            $anterior = $user->plano;

            // A auditoria da troca é registrada abaixo, com o contexto completo
            $this->auditoria->semRegistro(fn () => $user->forceFill(['plano_id' => $plano?->id])->save());
            $user->setRelation('plano', $plano);

            $historico = PlanoHistorico::create([
                'user_id'           => $user->id,
                'plano_anterior_id' => $anteriorId,
                'plano_novo_id'     => $plano?->id,
                'origem'            => $origem,
                'autor_id'          => $this->auditoria->autor()?->id,
                'referencia'        => $referencia,
                'observacao'        => $observacao,
            ]);

            $this->auditoria->registrar(
                'plano_alterado',
                $user,
                sprintf('Plano de %s: %s → %s', $user->email, $anterior?->nome ?? '—', $plano?->nome ?? '—'),
                ['plano' => ['de' => $anterior?->nome, 'para' => $plano?->nome], 'origem' => ['de' => null, 'para' => $origem->getLabel()]]
                    + ($observacao ? ['observacao' => ['de' => null, 'para' => $observacao]] : []),
            );

            return $historico;
        });
    }
}
