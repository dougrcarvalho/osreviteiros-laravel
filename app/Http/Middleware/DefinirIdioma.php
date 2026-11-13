<?php

namespace App\Http\Middleware;

use App\Support\Idiomas;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idiomas pelo prefixo da URL (/en, /fr, /es; sem prefixo = português).
 *
 * Roda ANTES do roteamento (middleware global): tira o prefixo do caminho, de modo que
 * as rotas continuam sendo declaradas uma vez só, sem prefixo — e passa a usar o prefixo
 * como raiz das URLs geradas (route(), url()), que então saem como /en/familias-revit.
 * As atualizações do Livewire (/en/livewire/update) também herdam o idioma por aqui.
 */
class DefinirIdioma
{
    public function handle(Request $request, Closure $next): Response
    {
        $idioma = Idiomas::daRequisicao($request);
        $prefixo = Idiomas::todos()[$idioma]['prefixo'];

        app()->setLocale($idioma);

        // Sem estado de uma requisição para outra (testes, Octane)
        URL::forceRootUrl(null);
        URL::useAssetOrigin(null);

        if ($prefixo) {
            $raiz = rtrim($request->root(), '/');

            // Arquivos (Vite, imagens) continuam na raiz, sem prefixo de idioma
            URL::useAssetOrigin($raiz);
            URL::forceRootUrl($raiz.'/'.$prefixo);

            $server = $request->server->all();
            $uri = $request->getRequestUri();
            $server['REQUEST_URI'] = preg_replace('#^/'.preg_quote($prefixo, '#').'(?=/|\?|$)#', '', $uri) ?: '/';
            if ($server['REQUEST_URI'][0] === '?') {
                $server['REQUEST_URI'] = '/'.$server['REQUEST_URI'];
            }

            $request = $request->duplicate(server: $server);
            app()->instance('request', $request);
        }

        return $next($request);
    }
}
