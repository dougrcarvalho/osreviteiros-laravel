<?php

namespace App\Services;

use App\Enums\TipoItem;
use App\Models\Categoria;
use App\Models\Download;
use App\Models\Fabricante;
use App\Models\Familia;
use App\Support\Idiomas;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Dados da página inicial: textos/listas de config('reviteiros.home') + contagens
 * e mais baixadas do banco (em cache, como arrays — o cache não guarda objetos).
 */
class HomeService
{
    public const CACHE_KEY = 'home.dados';

    public const CACHE_TTL = 3600;

    public function __construct(private TaxonomiaService $taxonomias) {}

    /** O cache guarda URLs e números formatados: uma entrada por idioma (o padrão mantém a chave original) */
    private function chaveCache(): string
    {
        return app()->getLocale() === Idiomas::padrao() ? self::CACHE_KEY : self::CACHE_KEY.'.'.app()->getLocale();
    }

    public function dados(): array
    {
        $config = config('reviteiros.home');

        $banco = Cache::remember($this->chaveCache(), self::CACHE_TTL, fn () => [
            'categorias' => $this->contagemCategorias(array_column($config['categorias'], 'slug')),
            'fabricantes' => Fabricante::whereIn('slug', array_column($config['fabricantes'], 'slug'))->pluck('slug')->all(),
            'populares' => $this->maisBaixadas(),
        ]);

        return [
            'total' => $config['total_familias'],
            'totalFmt' => Idiomas::numero($config['total_familias']),
            'buscas' => $this->buscas($config['buscas']),
            'video' => $config['video'],
            'pluginUrl' => $config['plugin_url'],
            'fabricantes' => $this->fabricantes($config['fabricantes'], $banco['fabricantes']),
            'categorias' => $this->categorias($config['categorias'], $banco['categorias']),
            'populares' => $banco['populares'],
            'planos' => $this->planos($config['planos']),
            'faq' => $this->faq($config['faq']),
            'depoimentos' => $this->depoimentos($config),
        ];
    }

    private function buscas(array $buscas): array
    {
        return array_map(fn (array $b) => [
            'termo' => __($b['termo']),
            'url' => route(TipoItem::from($b['biblioteca'])->rotaIndex(), ['busca' => $b['busca'] ?? $b['termo']]),
        ], $buscas);
    }

    private function fabricantes(array $lista, array $existentes): array
    {
        return array_map(fn (array $f) => [
            'nome' => $f['nome'],
            'logo' => isset($f['logo']) ? asset($f['logo']) : null,
            'url' => in_array($f['slug'], $existentes, true) ? route('fabricantes.show', $f['slug']) : null,
        ], $lista);
    }

    /** Ambiente sem termo correspondente no banco: sem link e contagem "[Nº]" */
    private function categorias(array $lista, array $contagens): array
    {
        return array_map(fn (array $c) => [
            'nome' => __($c['nome']),
            'destaque_mobile' => $c['destaque_mobile'] ?? false,
            'imagem' => $c['imagem'] ? asset($c['imagem']) : null,
            'url' => array_key_exists($c['slug'], $contagens) ? route('familias.index', ['cat' => $c['slug']]) : null,
            'total' => array_key_exists($c['slug'], $contagens) ? Idiomas::numero($contagens[$c['slug']]) : '[Nº]',
        ], $lista);
    }

    /** @return array<string, int> slug do termo → famílias ativas */
    private function contagemCategorias(array $slugs): array
    {
        $taxonomia = $this->taxonomias->porSlug(TipoItem::Familia, 'cat');

        if (! $taxonomia) {
            return [];
        }

        return Categoria::daTaxonomia($taxonomia)
            ->whereIn('slug', $slugs)
            ->withCount(['familias' => fn ($query) => $query->ativo()])
            ->pluck('familias_count', 'slug')
            ->all();
    }

    /** As 4 famílias mais baixadas nos últimos 7 dias, completadas pelo total histórico */
    private function maisBaixadas(int $limite = 4): array
    {
        $ids = Download::query()
            ->join('familias', 'familias.id', '=', 'downloads.familia_id')
            ->where('downloads.created_at', '>=', now()->subWeek())
            ->where('familias.ativo', true)
            ->where('familias.tipo', TipoItem::Familia->value)
            ->groupBy('downloads.familia_id')
            ->orderByRaw('count(*) desc')
            ->limit($limite)
            ->pluck('downloads.familia_id')
            ->all();

        if (count($ids) < $limite) {
            $ids = array_merge($ids, Familia::ativo()->tipo(TipoItem::Familia)
                ->whereNotIn('id', $ids)
                ->orderByDesc('downloads_count')
                ->limit($limite - count($ids))
                ->pluck('id')
                ->all());
        }

        $familias = Familia::with('fabricante')->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $familias->get($id))
            ->filter()
            ->map(fn (Familia $f) => [
                'nome' => $f->nome,
                'url' => $f->urlPublica(),
                'imagem' => $f->imagem_url,
                'fabricante' => $f->fabricante?->nome,
                'downloads' => Idiomas::numero($f->downloads_count),
                'selo' => $this->selo($f),
            ])
            ->values()
            ->all();
    }

    /** Plano mínimo mostrado no card: gratuita → Grátis; projetos → Premium; o resto → Basic */
    private function selo(Familia $familia): string
    {
        return match (true) {
            $familia->isGratuita() => 'gratis',
            $familia->tipo === TipoItem::Projeto => 'premium',
            default => 'basic',
        };
    }

    /** Perguntas e respostas no idioma atual (também alimentam o JSON-LD) */
    private function faq(array $faq): array
    {
        return array_map(fn (array $i) => [
            'pergunta' => __($i['pergunta']),
            'resposta' => __($i['resposta']),
        ], $faq);
    }

    private function planos(array $planos): array
    {
        return array_map(fn (array $p) => $p + [
            'precoFmt' => $p['preco'] > 0 ? 'R$ '.Idiomas::numero($p['preco'], 2) : 'R$ 0',
            'url' => $p['preco'] > 0
                ? (config("reviteiros.checkout.{$p['slug']}") ?: route('cadastro', ['plano' => $p['slug']]))
                : route('cadastro'),
        ], $planos);
    }

    /** Fictícios (`ilustrativo`) só aparecem em ambiente local */
    private function depoimentos(array $config): array
    {
        if (! $config['mostrar_depoimentos']) {
            return [];
        }

        return collect($config['depoimentos'])
            ->filter(fn (array $d) => empty($d['ilustrativo']) || app()->isLocal())
            ->map(fn (array $d) => $d + [
                'iniciais' => Str::of($d['nome'])->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''),
            ])
            ->values()
            ->all();
    }
}
