<?php

namespace App\Console\Commands;

use App\Enums\TipoItem;
use App\Models\Categoria;
use App\Models\Fabricante;
use App\Models\Familia;
use App\Models\Taxonomia;
use App\Services\AuditoriaService;
use App\Services\FamiliaService;
use App\Services\Importacao\WpExportReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Importa itens do catálogo a partir de uma exportação WXR do WordPress.
 * Idempotente: itens são identificados por `wp_post_id` (reexecutar atualiza).
 */
class ImportarWp extends Command
{
    protected $signature = 'reviteiros:import-wp
        {--file= : Arquivo .xml exportado do WordPress}
        {--tipo=familia : Tipo dos itens (familia|textura|projeto)}
        {--post-type=familias : post_type do WordPress a importar}
        {--ativar : Importa tudo como ativo, mesmo rascunhos}
        {--sem-imagens : Não baixa as imagens}
        {--dry-run : Mostra o que seria feito sem gravar}';

    protected $description = 'Importa famílias (e afins) de uma exportação XML do WordPress';

    /** Domínios do WP sem taxonomia própria: entram numa taxonomia existente (wp_taxonomia) */
    private const DOMINIO_ALIAS = ['escolha-por-categoria' => 'categorias'];

    /** @var array<string, Taxonomia> wp_taxonomia → Taxonomia */
    private array $taxonomias = [];

    private int $semImagem = 0;

    public function handle(WpExportReader $leitor, FamiliaService $familias, AuditoriaService $auditoria): int
    {
        $arquivo = base_path($this->option('file') ?: '');

        if (! is_file($arquivo)) {
            $this->error('Informe --file=arquivo.xml (relativo à raiz do projeto).');

            return self::FAILURE;
        }

        $tipo = TipoItem::tryFrom($this->option('tipo')) ?? throw new \InvalidArgumentException('Tipo inválido.');

        $this->taxonomias = Taxonomia::query()->doTipo($tipo)->get()->whereNotNull('wp_taxonomia')->keyBy('wp_taxonomia')->all();

        // Primeira passada: anexos (imagens) presentes no XML
        $anexos = [];
        foreach ($leitor->itens($arquivo) as $i) {
            if ($i['tipo'] === 'attachment' && $i['anexo_url']) {
                $anexos[$i['post_id']] = $i['anexo_url'];
            }
        }

        $total = 0;
        $auditoria->semRegistro(function () use ($leitor, $arquivo, $tipo, $anexos, $familias, &$total) {
            foreach ($leitor->itens($arquivo) as $item) {
                if ($item['tipo'] !== $this->option('post-type')) {
                    continue;
                }

                $this->importar($item, $tipo, $anexos, $familias);
                $total++;
            }
        });

        $this->info("{$total} item(ns) ".($this->option('dry-run') ? 'analisados (dry-run).' : 'importados.'));

        if ($this->semImagem) {
            $this->warn("{$this->semImagem} item(ns) sem imagem: sem anexo no XML ou falha no download (veja os avisos acima).");
        }

        return self::SUCCESS;
    }

    private function importar(array $item, TipoItem $tipo, array $anexos, FamiliaService $servico): void
    {
        $meta   = $item['meta'];
        $slug   = $item['slug'] ?: Str::slug($item['titulo']);
        $ativo  = $this->option('ativar') || $item['status'] === 'publish';
        // Nomes dos campos variam por tipo de conteúdo no WP
        $link = match ($tipo) {
            TipoItem::Textura => $meta['download-links'] ?? '',
            TipoItem::Projeto => ($meta['link-download'] ?? '') ?: ($meta['_todosarquivos'] ?? '') ?: ($meta['arquivo-rvt'] ?? ''),
            default           => ($meta['link_download'] ?? '') ?: ($meta['link-download'] ?? ''),
        };
        $gratis = ($meta['baixar-gratis'] ?? '') !== '' || ($meta['download_free'] ?? '') !== ''
            || ($tipo === TipoItem::Textura && ($meta['download-gratis'] ?? '') !== '');

        // Termos do WP agrupados por taxonomia do Laravel
        $porTaxonomia = [];
        $marca        = null;

        foreach ($item['termos'] as $t) {
            // "ACESSAR GRÁTIS" era um termo no WP; aqui é o campo `gratuita`
            if ($t['slug'] === 'acessar-gratis') {
                $gratis = true;

                continue;
            }

            $dominio = self::DOMINIO_ALIAS[$t['dominio']] ?? $t['dominio'];
            $taxo    = $this->taxonomias[$dominio] ?? null;

            if (! $taxo) {
                $this->warn("  [{$item['post_id']}] taxonomia WP sem equivalente: {$t['dominio']} ({$t['nome']})");

                continue;
            }

            if ($taxo->usaFabricante()) {
                $marca = $t;
            } else {
                $porTaxonomia[$taxo->id][] = $t;
            }
        }

        $resumo = collect($porTaxonomia)->flatten(1)->pluck('nome')->implode(', ');
        $this->line(sprintf('• #%d %s [%s] termos: %s | marca: %s', $item['post_id'], $item['titulo'], $ativo ? 'ativo' : 'inativo', $resumo ?: '—', $marca['nome'] ?? '—'));

        if ($this->option('dry-run')) {
            return;
        }

        $fabricante = $marca ? Fabricante::firstOrCreate(['slug' => $marca['slug']], ['nome' => $marca['nome']]) : null;

        $familia = Familia::where('wp_post_id', $item['post_id'])->first() ?? new Familia;
        $familia->fill([
            'tipo'          => $tipo,
            'nome'          => $item['titulo'],
            'slug'          => $familia->exists ? $familia->slug : $this->slugUnico($slug),
            'descricao'     => $item['conteudo'] ?: null,
            'gratuita'      => $gratis,
            'link_download' => $link ?: null,
            'curtidas'      => (int) ($meta['jet_engine_store_count_favoritos'] ?? 0),
            'fabricante_id' => $fabricante?->id,
            'ativo'         => $ativo,
            'wp_post_id'    => $item['post_id'],
        ]);
        $familia->created_at = $item['data'] ?: now();
        $familia->save();

        $termoIds = [];
        foreach ($porTaxonomia as $taxonomiaId => $termos) {
            foreach ($termos as $t) {
                $termoIds[] = Categoria::firstOrCreate(
                    ['taxonomia_id' => $taxonomiaId, 'slug' => $t['slug']],
                    ['nome' => $t['nome']],
                )->id;
            }
        }
        $servico->sincronizarCategorias($familia, $termoIds);

        if (! $this->option('sem-imagens')) {
            // Projetos não têm imagem de destaque: usa a primeira da galeria (ids separados por vírgula)
            $thumb = (int) ($meta['_thumbnail_id'] ?? 0)
                ?: (int) explode(',', ($meta['imagem-1'] ?? '') ?: ($meta['imagens-projeto'] ?? ''))[0];
            $this->baixarImagem($familia, $thumb, $anexos);
        }
    }

    private function slugUnico(string $slug): string
    {
        $final = $slug;
        for ($n = 2; Familia::where('slug', $final)->exists(); $n++) {
            $final = "{$slug}-{$n}";
        }

        return $final;
    }

    private function baixarImagem(Familia $familia, int $thumbId, array $anexos): void
    {
        if ($familia->imagem_path && Storage::disk(config('reviteiros.imagens.disk'))->exists($familia->imagem_path)) {
            return;
        }

        $url = $thumbId ? ($anexos[$thumbId] ?? $this->urlPelaApi($thumbId)) : null;

        if (! $url) {
            $this->semImagem++;
            $this->warn("    sem imagem (thumbnail {$thumbId})");

            return;
        }

        // 403 = bloqueio temporário por excesso de requisições: espera e tenta de novo
        $resposta = Http::timeout(60)->withUserAgent('Mozilla/5.0')
            ->retry([2000, 5000, 15000], throw: false, when: fn ($e, $req) => in_array($e->getCode(), [403, 429], true))
            ->get($url);
        usleep(150_000);

        if (! $resposta->successful()) {
            $this->semImagem++;
            $this->warn("    falha ao baixar {$url} ({$resposta->status()})");

            return;
        }

        $disk = config('reviteiros.imagens.disk');
        $path = 'familias/'.$familia->wp_post_id.'-'.Str::slug(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME)).'.'.(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg');

        Storage::disk($disk)->put($path, $resposta->body());
        $familia->update(['imagem_path' => $path, 'imagem_url' => Storage::disk($disk)->url($path)]);
        $this->line("    imagem: {$path}");
    }

    /** Mídia de item em rascunho só responde com autenticação (senha de aplicativo do WP) */
    private function urlPelaApi(int $id): ?string
    {
        $base = rtrim(config('reviteiros.wp.url'), '/');
        $req  = Http::timeout(30)->acceptJson()->withUserAgent('Mozilla/5.0');

        if ($user = config('reviteiros.wp.usuario')) {
            $req = $req->withBasicAuth($user, config('reviteiros.wp.senha_app'));
        }

        $r = $req->get("{$base}/wp-json/wp/v2/media/{$id}", ['_fields' => 'source_url']);

        return $r->successful() ? $r->json('source_url') : null;
    }
}
