<?php

namespace App\Console\Commands;

use App\Enums\OrigemPlano;
use App\Models\Plano;
use App\Models\User;
use App\Services\AssinaturaService;
use App\Services\AuditoriaService;
use App\Services\Importacao\SqlDumpReader;
use App\Support\WpPasswordHasher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Importa usuários do WordPress a partir dos dumps SQL de wp_users e wp_usermeta.
 * O plano vem do role (`planos.wp_role`); a senha antiga é guardada em users.wp_password_hash
 * e convertida para bcrypt no primeiro login (UserProviderComSenhaWp).
 * Idempotente: chave `wp_user_id` (com vínculo por e-mail para contas já existentes).
 */
class ImportarUsuariosWp extends Command
{
    protected $signature = 'reviteiros:import-usuarios
        {--users= : Dump SQL de wp_users (relativo à raiz do projeto)}
        {--meta= : Dump SQL de wp_usermeta (relativo à raiz do projeto)}
        {--atualizar-planos : Reaplica o plano de quem já foi importado e ainda está no plano vindo da importação}
        {--dry-run : Só mostra o relatório, sem gravar}';

    protected $description = 'Importa usuários e planos (roles) do WordPress, mantendo a senha antiga';

    public function handle(SqlDumpReader $leitor, AssinaturaService $assinaturas, AuditoriaService $auditoria): int
    {
        $arquivoUsers = base_path((string) $this->option('users'));
        $arquivoMeta  = base_path((string) $this->option('meta'));

        if (! is_file($arquivoUsers) || ! is_file($arquivoMeta)) {
            $this->error('Informe --users=… e --meta=… (dumps SQL, relativos à raiz do projeto).');

            return self::FAILURE;
        }

        $planos = Plano::whereNotNull('wp_role')->get()->keyBy('wp_role');
        $gratis = Plano::where('slug', Plano::GRATIS)->firstOrFail();

        // 1) Metadados necessários (roles e nomes)
        $this->info('Lendo wp_usermeta…');
        $caps = $primeiro = $ultimo = [];
        foreach ($leitor->linhas($arquivoMeta, 'wp_usermeta') as $m) {
            match ($m['meta_key']) {
                'wp_capabilities' => $caps[$m['user_id']]    = $this->roles((string) $m['meta_value']),
                'first_name'      => $primeiro[$m['user_id']] = (string) $m['meta_value'],
                'last_name'       => $ultimo[$m['user_id']]   = (string) $m['meta_value'],
                default           => null,
            };
        }

        // 2) E-mail repetido (maiúsculas/minúsculas ou contas duplicadas no WP): fica a conta de maior plano; empate = a mais antiga
        $this->info('Lendo wp_users…');
        $melhor = [];
        foreach ($leitor->linhas($arquivoUsers, 'wp_users') as $u) {
            $email = mb_strtolower(trim((string) $u['user_email']));
            $ordem = collect($caps[(int) $u['ID']] ?? [])->map(fn ($role) => $planos[$role]->ordem ?? 0)->max() ?? 0;
            if (! isset($melhor[$email]) || $ordem > $melhor[$email][0]) {
                $melhor[$email] = [$ordem, (int) $u['ID']];
            }
        }
        $vencedores = array_flip(array_column($melhor, 1));

        $r = ['lidos' => 0, 'novos' => 0, 'existentes' => 0, 'planos_atualizados' => 0, 'email_invalido' => [], 'email_duplicado' => [],
              'por_plano' => [], 'sem_role_de_plano' => 0, 'roles_desconhecidos' => [], 'equipe' => [], 'senha_legado' => 0, 'senha_sem_formato' => 0];
        $lote         = [];
        $senhaInutil  = Hash::make(Str::random(64)); // ninguém conhece: só vale o hash do WP ou o "esqueci a senha"
        $dry          = (bool) $this->option('dry-run');
        $existentesWp = User::whereNotNull('wp_user_id')->pluck('id', 'wp_user_id')->all();
        $existentesEm = User::pluck('id', 'email')->mapWithKeys(fn ($id, $e) => [mb_strtolower($e) => $id])->all();

        $auditoria->semRegistro(function () use ($vencedores, $leitor, $arquivoUsers, $caps, $primeiro, $ultimo, $planos, $gratis, $assinaturas, $dry, $senhaInutil, $existentesWp, $existentesEm, &$r, &$lote) {
            foreach ($leitor->linhas($arquivoUsers, 'wp_users') as $u) {
                $r['lidos']++;
                $wpId  = (int) $u['ID'];
                $email = mb_strtolower(trim((string) $u['user_email']));

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $r['email_invalido'][] = "#{$wpId} {$u['user_email']}";

                    continue;
                }
                if (! isset($vencedores[$wpId])) {
                    $r['email_duplicado'][] = "#{$wpId} {$email}";

                    continue;
                }

                // Plano: o mais alto entre os roles reconhecidos
                $roles = $caps[$wpId] ?? [];
                $plano = collect($roles)->map(fn ($role) => $planos[$role] ?? null)->filter()->sortByDesc('ordem')->first();
                if (! $plano) {
                    $r['sem_role_de_plano']++;
                    $plano = $gratis;
                }
                foreach ($roles as $role) {
                    if (! isset($planos[$role]) && $role !== 'subscriber') {
                        $r['roles_desconhecidos'][$role] = ($r['roles_desconhecidos'][$role] ?? 0) + 1;
                    }
                }
                if (in_array('administrator', $roles, true)) {
                    $r['equipe'][] = "#{$wpId} {$email} (roles: ".implode(',', $roles).') → plano '.$plano->slug;
                }
                $r['por_plano'][$plano->slug] = ($r['por_plano'][$plano->slug] ?? 0) + 1;

                $existenteId = $existentesWp[$wpId] ?? $existentesEm[$email] ?? null;

                if ($existenteId) {
                    $r['existentes']++;
                    if (! $dry) {
                        $this->atualizarExistente($existenteId, $wpId, $plano, $assinaturas, $r);
                    }

                    continue;
                }

                $r['novos']++;
                $legado = WpPasswordHasher::reconhece((string) $u['user_pass']) ? (string) $u['user_pass'] : null;
                $legado ? $r['senha_legado']++ : $r['senha_sem_formato']++;

                $nome = $this->corrigirAcentos(trim((string) $u['display_name']))
                    ?: $this->corrigirAcentos(trim(($primeiro[$wpId] ?? '').' '.($ultimo[$wpId] ?? '')))
                    ?: $this->corrigirAcentos((string) $u['user_login']);

                $data = $u['user_registered'] && ! str_starts_with((string) $u['user_registered'], '0000') ? $u['user_registered'] : now();
                $lote[] = [
                    'name' => Str::limit($nome ?: $email, 250, ''), 'email' => $email, 'password' => $senhaInutil,
                    'wp_password_hash' => $legado, 'wp_user_id' => $wpId, 'plano_id' => $plano->id, 'status' => 'ativo',
                    'created_at' => $data, 'updated_at' => $data,
                ];

                if (! $dry && count($lote) >= 500) {
                    $this->gravarLote($lote);
                    $lote = [];
                }
            }

            if (! $dry && $lote) {
                $this->gravarLote($lote);
                $lote = [];
            }
        });

        $this->relatorio($r, $dry);

        return self::SUCCESS;
    }

    /** Novos usuários + histórico de plano (origem importação) em lote, sem passar pelo model (23 mil linhas) */
    private function gravarLote(array $lote): void
    {
        DB::transaction(function () use ($lote) {
            DB::table('users')->insert($lote);

            $ids = DB::table('users')->whereIn('wp_user_id', array_column($lote, 'wp_user_id'))->pluck('plano_id', 'id');
            DB::table('plano_historicos')->insert($ids->map(fn ($planoId, $userId) => [
                'user_id' => $userId, 'plano_anterior_id' => null, 'plano_novo_id' => $planoId,
                'origem' => OrigemPlano::Importacao->value, 'referencia' => 'wp-import', 'created_at' => now(),
            ])->values()->all());
        });
    }

    /** Conta que já existe: vincula o wp_user_id; plano só muda com --atualizar-planos e se ainda é o da importação */
    private function atualizarExistente(int $id, int $wpId, Plano $plano, AssinaturaService $assinaturas, array &$r): void
    {
        $user = User::find($id);
        if (! $user->wp_user_id) {
            $user->forceFill(['wp_user_id' => $wpId])->saveQuietly();
        }

        if (! $this->option('atualizar-planos') || $user->plano_id === $plano->id) {
            return;
        }

        $ultima = \App\Models\PlanoHistorico::where('user_id', $user->id)->latest('id')->first();
        if ($ultima && $ultima->origem === OrigemPlano::Importacao) {
            $assinaturas->alterarPlano($user, $plano, OrigemPlano::Importacao, 'Reimportação do WordPress', 'wp-import');
            $r['planos_atualizados']++;
        }
    }

    /** @return list<string> */
    private function roles(string $serializado): array
    {
        $dados = @unserialize($serializado, ['allowed_classes' => false]);

        return is_array($dados) ? array_keys(array_filter($dados)) : [];
    }

    /** O dump veio com UTF-8 gravado duas vezes ("FranÃ§a"): desfaz quando o resultado é UTF-8 válido */
    private function corrigirAcentos(string $texto): string
    {
        if ($texto === '' || ! preg_match('/[ÃÂ]/u', $texto)) {
            return $texto;
        }

        $latin = @mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');

        return ($latin !== false && mb_check_encoding($latin, 'UTF-8') && ! str_contains($latin, "\u{FFFD}")) ? $latin : $texto;
    }

    private function relatorio(array $r, bool $dry): void
    {
        $this->newLine();
        $this->info(($dry ? '[dry-run] ' : '')."Usuários lidos: {$r['lidos']} | novos: {$r['novos']} | já existentes: {$r['existentes']} | planos atualizados: {$r['planos_atualizados']}");
        $this->table(['Plano', 'Usuários'], collect($r['por_plano'])->map(fn ($n, $p) => [$p, $n])->values()->all());
        $this->line("Senha do WordPress preservada: {$r['senha_legado']} | sem hash reconhecível (precisam do \"esqueci a senha\"): {$r['senha_sem_formato']}");
        $this->line("Sem role de plano (→ grátis): {$r['sem_role_de_plano']}");

        foreach (['roles_desconhecidos' => 'Roles sem plano (ignorados)', 'email_invalido' => 'E-mails inválidos (pulados)', 'email_duplicado' => 'Contas repetidas pelo e-mail (pulada a de menor plano/mais nova)', 'equipe' => 'Administradores do WP (importados como clientes; defina a função no painel)'] as $chave => $titulo) {
            if ($r[$chave]) {
                $this->warn("{$titulo}: ".count($r[$chave]));
                $itens = $chave === 'roles_desconhecidos' ? collect($r[$chave])->map(fn ($n, $k) => "{$k}: {$n}")->values()->all() : array_slice($r[$chave], 0, 15);
                foreach ($itens as $i) {
                    $this->line("  - {$i}");
                }
            }
        }
    }
}
