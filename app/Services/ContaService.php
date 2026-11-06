<?php

namespace App\Services;

use App\Enums\StatusConta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** Ações de atendimento sobre a conta de um usuário (painel) */
class ContaService
{
    public function __construct(private AuditoriaService $auditoria) {}

    public function bloquear(User $user, string $motivo): void
    {
        $this->auditoria->semRegistro(fn () => $user->forceFill([
            'status'          => StatusConta::Bloqueado,
            'bloqueio_motivo' => $motivo,
            'bloqueado_em'    => now(),
            'remember_token'  => Str::random(60),
        ])->save());

        $this->encerrarSessoes($user);

        $this->auditoria->registrar('conta_bloqueada', $user, "Bloqueou a conta de {$user->email}", [
            'motivo' => ['de' => null, 'para' => $motivo],
        ]);
    }

    public function desbloquear(User $user): void
    {
        $this->auditoria->semRegistro(fn () => $user->forceFill([
            'status'          => StatusConta::Ativo,
            'bloqueio_motivo' => null,
            'bloqueado_em'    => null,
        ])->save());

        $this->auditoria->registrar('conta_ativada', $user, "Reativou a conta de {$user->email}");
    }

    public function definirSenha(User $user, string $senha, bool $encerrarSessoes = true): void
    {
        $this->auditoria->semRegistro(fn () => $user->forceFill([
            'password'       => $senha,
            'remember_token' => Str::random(60),
        ])->save());

        if ($encerrarSessoes) {
            $this->encerrarSessoes($user);
        }

        $this->auditoria->registrar('senha_alterada', $user, "Definiu nova senha para {$user->email}");
    }

    public function enviarLinkSenha(User $user): string
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        $this->auditoria->registrar('reset_enviado', $user, "Enviou link de redefinição de senha para {$user->email}");

        return $status;
    }

    /** Sessões no banco (SESSION_DRIVER=database); o remember_token já foi trocado */
    private function encerrarSessoes(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
