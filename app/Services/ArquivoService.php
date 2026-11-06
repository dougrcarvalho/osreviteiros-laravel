<?php

namespace App\Services;

use App\Models\Familia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Arquivos de download enviados pelo painel.
 *
 * O arquivo fica no disco configurado (REVITEIROS_ARQUIVOS_DISK: local na VPS
 * ou s3) e a família guarda em `link_download` a URL da rota /arquivos/{path},
 * que confere o plano antes de entregar. Assim o formato do link não muda
 * quando o armazenamento migrar e o link não funciona se repassado a quem
 * não tem permissão.
 */
class ArquivoService
{
    public const DIRETORIO = 'familias';

    public function disco(): string
    {
        return config('reviteiros.arquivos.disk');
    }

    public function linkPara(string $path): string
    {
        return route('arquivos.download', ['path' => $path]);
    }

    /** Associa um arquivo recém-enviado à família, removendo o anterior */
    public function anexar(Familia $familia, string $path, ?string $nomeOriginal = null): void
    {
        $disk = $this->disco();

        if ($familia->arquivo_path && $familia->arquivo_path !== $path) {
            $this->apagar($familia);
        }

        $familia->forceFill([
            'arquivo_disk'    => $disk,
            'arquivo_path'    => $path,
            'arquivo_nome'    => $nomeOriginal ?? basename($path),
            'arquivo_tamanho' => Storage::disk($disk)->size($path),
            'link_download'   => $this->linkPara($path),
        ])->save();
    }

    /** Troca para link externo: apaga o arquivo enviado, se houver */
    public function usarLinkExterno(Familia $familia, ?string $url): void
    {
        $this->apagar($familia);

        $familia->forceFill([
            'arquivo_disk'    => null,
            'arquivo_path'    => null,
            'arquivo_nome'    => null,
            'arquivo_tamanho' => null,
            'link_download'   => $url,
        ])->save();
    }

    public function apagar(Familia $familia): void
    {
        if ($familia->arquivo_path && $familia->arquivo_disk) {
            Storage::disk($familia->arquivo_disk)->delete($familia->arquivo_path);
        }
    }

    public function responder(Familia $familia): Response
    {
        $disk = Storage::disk($familia->arquivo_disk);
        $nome = $familia->arquivo_nome ?: basename($familia->arquivo_path);

        // S3 e similares: URL temporária assinada direto do bucket
        if (method_exists($disk, 'providesTemporaryUrls') && $disk->providesTemporaryUrls() && $familia->arquivo_disk !== 'local') {
            return redirect()->away($disk->temporaryUrl($familia->arquivo_path, now()->addMinutes(10), [
                'ResponseContentDisposition' => 'attachment; filename="'.addslashes($nome).'"',
            ]));
        }

        abort_unless($disk->exists($familia->arquivo_path), 404);

        return $disk->download($familia->arquivo_path, $nome);
    }
}
