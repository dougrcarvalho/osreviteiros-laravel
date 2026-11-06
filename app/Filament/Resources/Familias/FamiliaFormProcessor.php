<?php

namespace App\Filament\Resources\Familias;

use App\Enums\TipoItem;
use App\Models\Categoria;
use App\Models\Familia;
use App\Services\ArquivoService;
use App\Services\FamiliaService;
use App\Services\TaxonomiaService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Traduz o formulário do painel ⇄ model: termos por taxonomia (`termos.{slug}`
 * → pivot), fabricante e imagem/arquivo por upload ou link.
 */
class FamiliaFormProcessor
{
    private const CAMPOS_EXTRAS = [
        'termos', 'imagem_origem', 'imagem_upload', 'arquivo_origem', 'arquivo_upload', 'arquivo_upload_nome',
    ];

    public function __construct(
        private FamiliaService $familias,
        private ArquivoService $arquivos,
        private TaxonomiaService $taxonomias,
    ) {}

    /** Estado inicial do formulário de edição */
    public function preencher(Familia $familia, array $data): array
    {
        $data['termos'] = $familia->categorias()->with('taxonomia')->get()
            ->groupBy(fn (Categoria $c) => $c->taxonomia->slug)
            ->map(fn ($termos) => $termos->pluck('id')->values()->all())
            ->all();

        $data['imagem_origem'] = $familia->imagem_path || ! $familia->imagem_url ? 'upload' : 'url';
        $data['imagem_upload'] = $familia->imagem_path;

        $data['arquivo_origem'] = $familia->temArquivoLocal() || ! $familia->link_download ? 'upload' : 'url';
        $data['arquivo_upload'] = $familia->arquivo_path;
        $data['link_download']  = $familia->temArquivoLocal() ? null : $familia->link_download;

        return $data;
    }

    /** @return array{0: array, 1: array} [dados do model, extras] */
    public function separar(array $data): array
    {
        $extras = Arr::only($data, self::CAMPOS_EXTRAS);
        $model  = Arr::except($data, self::CAMPOS_EXTRAS);

        // Link/imagem são aplicados em aplicar(), conforme a origem escolhida
        unset($model['link_download'], $model['imagem_url']);
        $extras['link_download'] = $data['link_download'] ?? null;
        $extras['imagem_url']    = $data['imagem_url'] ?? null;

        // Tipo sem taxonomia de marca (ex.: projetos) não guarda fabricante
        $tipo = $model['tipo'] instanceof TipoItem ? $model['tipo'] : TipoItem::tryFrom((string) ($model['tipo'] ?? ''));
        if ($tipo && ! $this->taxonomias->doTipo($tipo)->contains(fn ($t) => $t->usaFabricante())) {
            $model['fabricante_id'] = null;
        }

        return [$model, $extras];
    }

    public function aplicar(Familia $familia, array $extras): void
    {
        $this->aplicarTermos($familia, $extras);
        $this->aplicarImagem($familia, $extras);
        $this->aplicarArquivo($familia, $extras);
    }

    private function aplicarTermos(Familia $familia, array $extras): void
    {
        $ids = collect($extras['termos'] ?? [])->flatten()->filter()->map(fn ($id) => (int) $id)->unique()->all();

        // Só termos das taxonomias do tipo do item (ignora ids forjados ou de outro tipo)
        $validos = Categoria::query()
            ->whereIn('id', $ids)
            ->whereHas('taxonomia', fn ($t) => $t->doTipo($familia->tipo)->ativas()->deTermos())
            ->pluck('id')
            ->all();

        $this->familias->sincronizarCategorias($familia, $validos);
    }

    private function aplicarImagem(Familia $familia, array $extras): void
    {
        $disk = config('reviteiros.imagens.disk');
        $path = $this->unico($extras['imagem_upload'] ?? null);

        if (($extras['imagem_origem'] ?? 'upload') === 'url') {
            $novo = ['imagem_path' => null, 'imagem_url' => $extras['imagem_url'] ?: null];
        } else {
            $novo = ['imagem_path' => $path, 'imagem_url' => $path ? Storage::disk($disk)->url($path) : null];
        }

        if ($familia->imagem_path && $familia->imagem_path !== $novo['imagem_path']) {
            Storage::disk($disk)->delete($familia->imagem_path);
        }

        $familia->fill($novo);
        if ($familia->isDirty()) {
            $familia->save();
        }
    }

    private function aplicarArquivo(Familia $familia, array $extras): void
    {
        if (($extras['arquivo_origem'] ?? 'upload') === 'url') {
            if ($familia->temArquivoLocal() || $familia->link_download !== ($extras['link_download'] ?: null)) {
                $this->arquivos->usarLinkExterno($familia, $extras['link_download'] ?: null);
            }

            return;
        }

        $path = $this->unico($extras['arquivo_upload'] ?? null);

        if (! $path) {
            if ($familia->temArquivoLocal() || $familia->link_download) {
                $this->arquivos->usarLinkExterno($familia, null);
            }

            return;
        }

        if ($path !== $familia->arquivo_path) {
            $this->arquivos->anexar($familia, $path, $this->unico($extras['arquivo_upload_nome'] ?? null));
        }
    }

    /** FileUpload de arquivo único pode vir como string ou [uuid => path] */
    private function unico(mixed $valor): ?string
    {
        if (is_array($valor)) {
            $valor = Arr::first($valor);
        }

        return filled($valor) ? (string) $valor : null;
    }
}
