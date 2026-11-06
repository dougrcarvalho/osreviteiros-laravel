<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaOpcoesRequest;
use App\Http\Requests\FamiliaFiltroRequest;
use App\Http\Resources\FamiliaCardResource;
use App\Services\CategoriaService;
use App\Services\FamiliaBuscaService;
use App\Services\TaxonomiaService;
use Illuminate\Http\JsonResponse;

class FamiliaController extends Controller
{
    public function index(FamiliaFiltroRequest $request, FamiliaBuscaService $busca): JsonResponse
    {
        $paginator = $busca->query($request->filtros())
            ->with('fabricante')
            ->paginate($request->perPage());

        return response()->json([
            'data' => FamiliaCardResource::collection($paginator->getCollection())->resolve($request),
            'meta' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /** Opções de uma taxonomia dentro do contexto acumulativo dos filtros */
    public function categorias(CategoriaOpcoesRequest $request, CategoriaService $categorias, TaxonomiaService $taxonomias): JsonResponse
    {
        $taxonomia = $taxonomias->porSlug($request->validated('biblioteca'), $request->validated('taxonomia'));

        abort_unless($taxonomia, 404, 'Taxonomia não encontrada nesta biblioteca.');

        return response()->json([
            'data' => $categorias->opcoes($taxonomia, $request->filtros())->values(),
        ]);
    }
}
