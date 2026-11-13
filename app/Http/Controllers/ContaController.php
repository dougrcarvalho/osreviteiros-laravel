<?php

namespace App\Http\Controllers;

use App\Enums\TipoItem;
use App\Http\Requests\AtualizarPerfilRequest;
use App\Http\Requests\SolicitarCancelamentoRequest;
use App\Models\Plano;
use App\Services\CadastroService;
use App\Services\CancelamentoService;
use App\Services\PlanoService;
use App\Services\UsoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Área do aluno/assinante: perfil, plano e histórico de uso */
class ContaController extends Controller
{
    public function perfil(Request $request, UsoService $uso): View
    {
        return view('conta.perfil', [
            'resumo'    => $uso->resumo($request->user()),
            'favoritos' => $request->user()->favoritos()->ativo()->count(),
        ]);
    }

    public function atualizarPerfil(AtualizarPerfilRequest $request): RedirectResponse
    {
        $request->user()->update(['name' => $request->validated('name')]);

        return redirect()->route('conta.perfil')->with('success', __('Dados atualizados.'));
    }

    public function plano(Request $request, PlanoService $planos, CadastroService $cadastro, CancelamentoService $cancelamento): View
    {
        $user  = $request->user();
        $atual = $user->plano;

        // Upgrade = planos pagos acima do atual (o Plus foi descontinuado e não é oferecido)
        $upgrades = $planos->pagos()
            ->reject(fn (Plano $p) => $p->slug === Plano::PLUS)
            ->filter(fn (Plano $p) => $p->ordem > ($atual?->ordem ?? 0))
            ->map(fn (Plano $p) => [
                'plano'    => $p,
                'info'     => $this->info($p),
                'checkout' => $cadastro->checkoutUrl($p, $user),
            ])
            ->values();

        return view('conta.plano', [
            'atual'      => $atual,
            'info'       => $atual ? $this->info($atual) : null,
            'upgrades'   => $upgrades,
            'desde'      => $user->planoHistoricos()->where('plano_novo_id', $user->plano_id)->first()?->created_at,
            'historico'  => $user->planoHistoricos()->with(['planoAnterior', 'planoNovo'])->limit(20)->get(),
            'pendente'   => $cancelamento->pendente($user),
        ]);
    }

    public function solicitarCancelamento(SolicitarCancelamentoRequest $request, CancelamentoService $cancelamento): RedirectResponse
    {
        $pedido = $cancelamento->solicitar($request->user(), $request->validated('motivo'));

        return redirect()->to(route('conta.plano').'#cancelar')->with(
            $pedido ? 'success' : 'erro',
            $pedido ? __('Pedido de cancelamento registrado. Nossa equipe vai confirmá-lo em breve.') : __('Você não tem um plano pago para cancelar.')
        );
    }

    public function desfazerCancelamento(Request $request, CancelamentoService $cancelamento): RedirectResponse
    {
        $cancelamento->desfazer($request->user());

        return redirect()->route('conta.plano')->with('success', __('Pedido de cancelamento retirado. Seu plano continua ativo.'));
    }

    public function historico(Request $request, UsoService $uso): View
    {
        $tipo = TipoItem::tryFrom((string) $request->query('tipo'));

        return view('conta.historico', [
            'resumo'    => $uso->resumo($request->user()),
            'tipo'      => $tipo,
            'downloads' => $uso->downloads($request->user(), $tipo)->paginate(25)->withQueryString(),
        ]);
    }

    /** Preço e benefícios exibidos para o plano (textos da home; Plus conta como Basic) */
    private function info(Plano $plano): ?array
    {
        $planos = collect(config('reviteiros.home.planos'))->keyBy('slug');

        return $planos->get($plano->slug === Plano::PLUS ? 'basic' : $plano->slug);
    }
}
