<?php

namespace App\Services;

use App\Enums\TipoItem;
use App\Models\Familia;
use App\Models\Taxonomia;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monta a query de itens a partir dos filtros da URL.
 *
 * Filtros (todos acumulativos, AND):
 *   biblioteca  familia|textura|projeto
 *   gratis      1 = só "acessar grátis"
 *   marca       slug do fabricante (vale em qualquer biblioteca)
 *   busca       texto livre
 *   <slug>      slug de qualquer taxonomia da biblioteca (cat, sub, cor, tipo…)
 *
 * Os slugs de taxonomia só valem com `biblioteca` definida, pois o mesmo
 * slug ("cat") existe em cada biblioteca com termos diferentes.
 */
class FamiliaBuscaService
{
    public const ORDENS = ['date', 'az', 'curtidas'];

    public const PER_PAGE = 48;

    public const MAX_PER_PAGE = 96;

    /** Tamanho mínimo de token do FULLTEXT do InnoDB (innodb_ft_min_token_size) */
    private const FT_MIN_TOKEN = 3;

    private const MAX_PALAVRAS = 8;

    public function __construct(private TaxonomiaService $taxonomias) {}

    public function query(array $filtros): Builder
    {
        $query = Familia::query()->ativo();

        $this->aplicarFiltros($query, $filtros);
        $this->ordenar($query, $filtros['orderby'] ?? 'date');

        return $query;
    }

    /**
     * Aplica biblioteca + gratuita + marca + taxonomias + busca
     * (também usada dentro de whereHas para calcular opções).
     */
    public function aplicarFiltros(Builder $query, array $filtros): Builder
    {
        $tipo = TipoItem::tryFrom((string) ($filtros['biblioteca'] ?? ''));

        if ($tipo) {
            $query->tipo($tipo);
        }

        if (! empty($filtros['gratis'])) {
            $query->where('gratuita', true);
        }

        if (! empty($filtros['marca'])) {
            $query->whereHas('fabricante', fn (Builder $f) => $f->where('slug', $filtros['marca']));
        }

        if ($tipo) {
            /** @var Taxonomia $taxonomia */
            foreach ($this->taxonomias->doTipo($tipo) as $taxonomia) {
                $valor = $filtros[$taxonomia->slug] ?? null;

                if ($taxonomia->usaFabricante() || blank($valor) || ! is_string($valor)) {
                    continue;
                }

                $query->comTermo($taxonomia->id, $valor);
            }
        }

        if (! empty($filtros['busca'])) {
            $this->aplicarBusca($query, $filtros['busca']);
        }

        return $query;
    }

    /**
     * Cada palavra precisa aparecer em `busca_texto`, que contém nome,
     * descrição, tipo, fabricante e o nome de TODOS os termos do item.
     */
    public function aplicarBusca(Builder $query, string $busca): Builder
    {
        $termosFulltext = [];

        foreach ($this->palavras($busca) as $palavra) {
            if (mb_strlen($palavra) >= self::FT_MIN_TOKEN) {
                $termosFulltext[] = '+'.$this->radical($palavra).'*';
            } else {
                // Tokens curtos não entram no índice FULLTEXT
                $query->where('busca_texto', 'like', '%'.addcslashes($palavra, '%_\\').'%');
            }
        }

        if ($termosFulltext) {
            $query->whereFullText('busca_texto', implode(' ', $termosFulltext), ['mode' => 'boolean']);
        }

        return $query;
    }

    /** @return list<string> */
    public function palavras(string $busca): array
    {
        $palavras = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($busca), -1, PREG_SPLIT_NO_EMPTY);

        return array_slice(array_values(array_unique($palavras)), 0, self::MAX_PALAVRAS);
    }

    /**
     * Radical simples para plural em português, usado com busca por prefixo:
     * "cadeiras" → "cadeira*", "portões" → "port*".
     */
    private function radical(string $palavra): string
    {
        if (mb_strlen($palavra) > 5 && preg_match('/(ões|ães|ãos)$/u', $palavra)) {
            return mb_substr($palavra, 0, -3);
        }

        if (mb_strlen($palavra) > 4 && str_ends_with($palavra, 's')) {
            return mb_substr($palavra, 0, -1);
        }

        return $palavra;
    }

    private function ordenar(Builder $query, ?string $orderby): void
    {
        match ($orderby) {
            'az'       => $query->orderBy('nome'),
            'curtidas' => $query->orderByDesc('curtidas')->orderByDesc('id'),
            default    => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }
}
