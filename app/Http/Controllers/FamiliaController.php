<?php

namespace App\Http\Controllers;

use App\Enums\TipoItem;
use App\Models\Familia;
use App\Services\DownloadService;
use App\Services\ListagemSeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Bibliotecas: /familias-revit, /texturas-revit-bim, /projetos-prontos-revit-bim (rotas em routes/web.php) */
class FamiliaController extends Controller
{
    /** Listagem — também atende /{biblioteca}/page/{n}/ */
    public function index(Request $request, ListagemSeoService $seo): View
    {
        $tipo = TipoItem::from($request->route('biblioteca'));

        return view('familias.index', [
            'tipo' => $tipo,
            'seo'  => $seo->montar($request, route($tipo->rotaIndex()), ['biblioteca' => $tipo->value]),
        ]);
    }

    // A ordem segue a da rota: {familia} (URL) antes do `biblioteca` (default)
    public function show(Request $request, Familia $familia, string $biblioteca, DownloadService $download): View|RedirectResponse
    {
        abort_unless($familia->ativo || $request->user()?->isFuncionario(), 404);

        // /familias-revit/uma-textura → URL correta da biblioteca
        if ($familia->tipo->segmento() !== $biblioteca) {
            return redirect()->to($familia->urlPublica(), 301);
        }

        $familia->load([
            'fabricante',
            'categorias' => fn ($q) => $q->with('taxonomia')->orderBy('nome'),
        ]);

        return view('familias.show', [
            'familia' => $familia,
            'botao'   => $download->estadoBotao($request->user(), $familia),
            // Termos agrupados na ordem das taxonomias do tipo
            'grupos'  => $familia->categorias
                ->filter(fn ($c) => $c->taxonomia?->ativa)
                ->groupBy('taxonomia_id')
                ->sortBy(fn ($termos) => $termos->first()->taxonomia->ordem),
        ]);
    }
}
