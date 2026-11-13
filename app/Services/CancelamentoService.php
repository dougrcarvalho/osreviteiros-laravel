<?php

namespace App\Services;

use App\Models\SolicitacaoCancelamento;
use App\Models\User;

/**
 * Cancelamento pedido pelo assinante. Como a cobrança é no gateway (Hotmart),
 * o pedido fica registrado e visível na Auditoria; o plano só muda quando a equipe
 * (ou o webhook) aplica o plano Grátis — nesse momento o pedido é dado como atendido
 * (AssinaturaService::alterarPlano).
 */
class CancelamentoService
{
    public function __construct(private AuditoriaService $auditoria) {}

    public function pendente(User $user): ?SolicitacaoCancelamento
    {
        return $user->cancelamentos()->pendentes()->latest('id')->first();
    }

    public function solicitar(User $user, ?string $motivo = null): ?SolicitacaoCancelamento
    {
        if (! $user->plano?->isPago()) {
            return null;
        }

        if ($existente = $this->pendente($user)) {
            return $existente;
        }

        $pedido = $user->cancelamentos()->create([
            'plano_id' => $user->plano_id,
            'motivo'   => filled($motivo) ? mb_substr(trim($motivo), 0, 1000) : null,
        ]);

        $this->auditoria->registrar(
            'cancelamento_solicitado',
            $user,
            "{$user->email} solicitou o cancelamento do plano {$user->plano->nome}",
            filled($pedido->motivo) ? ['motivo' => ['de' => null, 'para' => $pedido->motivo]] : null,
            forcar: true,
        );

        return $pedido;
    }

    /** O assinante desistiu do pedido enquanto ele ainda estava pendente */
    public function desfazer(User $user): void
    {
        $pedido = $this->pendente($user);

        if (! $pedido) {
            return;
        }

        $pedido->update(['atendida_em' => now()]);

        $this->auditoria->registrar('cancelamento_desfeito', $user, "{$user->email} desistiu do cancelamento do plano", forcar: true);
    }

    /** Plano virou grátis: os pedidos pendentes foram atendidos */
    public function concluirPendentes(User $user): void
    {
        $user->cancelamentos()->pendentes()->update(['atendida_em' => now()]);
    }
}
