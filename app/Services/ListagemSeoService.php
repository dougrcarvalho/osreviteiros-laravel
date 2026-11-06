<?php

namespace App\Services;

use App\Support\UrlListagem;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

/**
 * Paginação SEO das listagens (/familias-revit/page/3/):
 * - /page/1/ (ou menor) → 301 para a URL base
 * - página além da última → 404
 * - canonical, prev e next para o <head>
 */
class ListagemSeoService
{
    public function __construct(private FamiliaBuscaService $busca) {}

    /**
     * @param  array<string, mixed>  $filtrosFixos  biblioteca da rota ou marca da tela do fabricante
     * @return array{base: string, pagina: int, ultima: int, total: int, query: array<string, string>, canonical: string, prev: ?string, next: ?string}
     */
    public function montar(Request $request, string $base, array $filtrosFixos): array
    {
        $query      = UrlListagem::queryLimpa($request->query());
        $paginaRota = $request->route('pagina');

        if ($paginaRota !== null && (int) $paginaRota <= 1) {
            throw new HttpResponseException(redirect()->to(UrlListagem::pagina($base, 1, $query), 301));
        }

        $pagina = max(1, (int) ($paginaRota ?? 1));
        $total  = $this->busca->query(array_merge($query, $filtrosFixos))->reorder()->count();
        $ultima = UrlListagem::ultimaPagina($total, FamiliaBuscaService::PER_PAGE);

        abort_if($pagina > $ultima, 404);

        return [
            'base'      => $base,
            'pagina'    => $pagina,
            'ultima'    => $ultima,
            'total'     => $total,
            'query'     => $query,
            'canonical' => UrlListagem::pagina($base, $pagina, $query),
            'prev'      => $pagina > 1 ? UrlListagem::pagina($base, $pagina - 1, $query) : null,
            'next'      => $pagina < $ultima ? UrlListagem::pagina($base, $pagina + 1, $query) : null,
        ];
    }
}
