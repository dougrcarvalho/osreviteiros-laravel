<?php

namespace App\Http\Resources;

use App\Services\DownloadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Familia */
class FamiliaCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user     = $request->user('sanctum');
        $download = app(DownloadService::class);

        return [
            'id'            => $this->id,
            'tipo'          => $this->tipo->value,
            'nome'          => $this->nome,
            'slug'          => $this->slug,
            'url'           => $this->urlPublica(),
            'imagem'        => $this->imagem_url,
            'fabricante'    => $this->fabricante?->nome,
            'is_novo'       => $this->is_novo,
            'curtidas'      => $this->curtidas,
            'is_gratuita'   => $this->isGratuita(),
            'is_logged'     => (bool) $user,
            'has_plano'     => (bool) $user?->hasPlano(),
            'botao'         => $download->estadoBotao($user, $this->resource),
            'link_download' => $download->linkPara($user, $this->resource),
        ];
    }
}
