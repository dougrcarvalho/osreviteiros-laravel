<?php

namespace App\Http\Controllers;

use App\Models\Fabricante;
use App\Services\ListagemSeoService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class FabricanteController extends Controller
{
    public function index(): View
    {
        $fabricantes = Fabricante::query()
            ->withCount(['familias' => fn (Builder $q) => $q->ativo()])
            ->orderBy('nome')
            ->get();

        return view('fabricantes.index', compact('fabricantes'));
    }

    /** Tela da marca: todas as famílias da marca, depois filtráveis pelas outras categorias */
    public function show(Request $request, Fabricante $fabricante, ListagemSeoService $seo): View
    {
        return view('fabricantes.show', [
            'fabricante' => $fabricante,
            'seo'        => $seo->montar($request, route('fabricantes.show', $fabricante), ['marca' => $fabricante->slug]),
        ]);
    }
}
