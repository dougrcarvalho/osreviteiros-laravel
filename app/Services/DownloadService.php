<?php

namespace App\Services;

use App\Models\Download;
use App\Models\Familia;
use App\Models\User;

/**
 * Única porta de saída de `link_download`.
 */
class DownloadService
{
    /** Estados do botão de download no card */
    public const CADASTRAR = 'cadastrar';         // não logado → popup de cadastro

    public const ASSINAR = 'assinar';             // grátis + família paga → popup de upgrade

    public const BAIXAR_GRATIS = 'baixar-gratis'; // grátis + família "acessar-gratis"

    public const UPGRADE = 'upgrade';             // pago, mas o plano não cobre o tipo (Basic + projeto) → popup do Premium

    public const BAIXAR = 'baixar';               // plano cobre o tipo do item

    public function podeBaixar(?User $user, Familia $familia): bool
    {
        if (! $user || $user->isBloqueado()) {
            return false;
        }

        // Funcionários baixam tudo (conferência de arquivos)
        return $user->isFuncionario() || $familia->isGratuita() || $user->planoLibera($familia->tipo);
    }

    public function linkPara(?User $user, Familia $familia): ?string
    {
        return $this->podeBaixar($user, $familia) ? $familia->link_download : null;
    }

    /** Métrica de downloads (dashboard / ranking) */
    public function registrar(?User $user, Familia $familia): void
    {
        Download::create(['familia_id' => $familia->id, 'user_id' => $user?->id]);
        Familia::whereKey($familia->id)->increment('downloads_count');
    }

    /** Mensagem para quem não pode baixar (API / rota de arquivo) */
    public function motivoBloqueio(?User $user, Familia $familia): string
    {
        return $this->estadoBotao($user, $familia) === self::UPGRADE
            ? 'Projetos prontos são exclusivos do plano Premium.'
            : 'Assine um plano para baixar este item.';
    }

    public function estadoBotao(?User $user, Familia $familia): string
    {
        return match (true) {
            ! $user                => self::CADASTRAR,
            $user->isFuncionario(), $user->planoLibera($familia->tipo) => self::BAIXAR,
            $familia->isGratuita() => self::BAIXAR_GRATIS,
            $user->hasPlano()      => self::UPGRADE,
            default                => self::ASSINAR,
        };
    }
}
