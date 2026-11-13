<?php

namespace App\Http\Controllers;

use App\Models\Familia;
use App\Services\DownloadService;
use App\Services\FavoritoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoritoController extends Controller
{
    /** Página "Meus favoritos": itens ativos que o usuário favoritou, os mais recentes primeiro */
    public function index(Request $request, DownloadService $download): View
    {
        $user = $request->user();

        $familias = $user->favoritos()
            ->ativo()
            ->with('fabricante')
            ->orderByPivot('created_at', 'desc')
            ->orderByPivot('id', 'desc')
            ->paginate(48)
            ->withQueryString();

        return view('favoritos.index', [
            'familias' => $familias,
            'botoes'   => $familias->getCollection()->mapWithKeys(fn (Familia $f) => [$f->id => $download->estadoBotao($user, $f)]),
        ]);
    }

    /** Favorita/desfavorita (chamado pelo botão de coração, sem recarregar a página) */
    public function alternar(Request $request, Familia $familia, FavoritoService $favoritos): JsonResponse
    {
        abort_unless($familia->ativo, 404);

        return response()->json(['favorito' => $favoritos->alternar($request->user(), $familia)]);
    }
}
