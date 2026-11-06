<?php

namespace App\Http\Controllers;

use App\Models\Familia;
use App\Services\ArquivoService;
use App\Services\DownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    public function download(Request $request, Familia $familia, DownloadService $download): JsonResponse|RedirectResponse
    {
        abort_unless($familia->ativo, 404);

        $user = $request->user() ?? $request->user('sanctum');

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['error' => 'nao_autenticado', 'redirect' => route('login')], 401)
                : redirect()->guest(route('cadastro'));
        }

        $link = $download->linkPara($user, $familia);

        if (! $link) {
            return $request->expectsJson()
                ? response()->json(['error' => 'sem_permissao', 'message' => $download->motivoBloqueio($user, $familia)], 403)
                : redirect()->to($familia->urlPublica())->with('upgrade', true);
        }

        $download->registrar($user, $familia);

        return $request->expectsJson()
            ? response()->json(['url' => $link])
            : redirect()->away($link);
    }

    /**
     * Entrega um arquivo enviado pelo painel (link salvo em link_download).
     * Confere a permissão de novo: o link não funciona se for repassado.
     */
    public function arquivo(Request $request, string $path, DownloadService $download, ArquivoService $arquivos): Response
    {
        $familia = Familia::where('arquivo_path', $path)->firstOrFail();

        abort_unless($familia->ativo || $request->user()?->isFuncionario(), 404);

        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        abort_unless($download->podeBaixar($request->user(), $familia), 403, $download->motivoBloqueio($request->user(), $familia));

        return $arquivos->responder($familia);
    }
}
