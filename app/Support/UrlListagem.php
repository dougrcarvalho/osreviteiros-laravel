<?php

namespace App\Support;

/**
 * URLs das listagens com paginação "SEO friendly":
 *   página 1  → /familias-revit?cat=automoveis
 *   página 3  → /familias-revit/page/3/?cat=automoveis
 * (mesmo formato do WordPress). Usado pelo grid (infinite scroll troca a
 * URL conforme a rolagem) e pelas tags canonical/prev/next do <head>.
 */
class UrlListagem
{
    /** @param  array<string, string>  $query */
    public static function pagina(string $base, int $pagina, array $query = []): string
    {
        $base = rtrim($base, '/');
        $url  = $pagina > 1 ? "{$base}/page/{$pagina}/" : $base;

        $query = array_filter($query, fn ($v) => $v !== null && $v !== '');

        return $query ? $url.'?'.http_build_query($query) : $url;
    }

    /**
     * Parâmetros de filtro válidos da query (strings simples), sem `page`.
     *
     * @return array<string, string>
     */
    public static function queryLimpa(array $query): array
    {
        $limpa = [];

        foreach ($query as $chave => $valor) {
            if (is_string($chave) && $chave !== 'page' && preg_match('/^[a-z0-9_-]{1,50}$/', $chave)
                && is_string($valor) && $valor !== '' && mb_strlen($valor) <= 200) {
                $limpa[$chave] = $valor;
            }
        }

        return $limpa;
    }

    public static function ultimaPagina(int $total, int $porPagina): int
    {
        return max(1, (int) ceil($total / max($porPagina, 1)));
    }
}
