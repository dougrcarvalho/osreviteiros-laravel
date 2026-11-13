<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Idiomas do site público e URLs equivalentes em cada um (hreflang / seletor). */
class Idiomas
{
    /** @return array<string, array<string, mixed>> */
    public static function todos(): array
    {
        return config('idiomas.disponiveis');
    }

    public static function padrao(): string
    {
        return config('idiomas.padrao');
    }

    public static function htmlLang(): string
    {
        return self::todos()[self::atual()]['html'];
    }

    public static function ogLocale(): string
    {
        return self::todos()[self::atual()]['og'];
    }

    /** Número no formato do idioma atual (5.422 / 5,422 / 5 422) */
    public static function numero(int|float $valor, int $decimais = 0): string
    {
        [$dec, $mil] = match (self::atual()) {
            'en' => ['.', ','],
            'fr' => [',', "\u{202F}"],
            default => [',', '.'],
        };

        return number_format($valor, $decimais, $dec, $mil);
    }

    /** Prefixos de URL dos idiomas não padrão (en, fr, es) */
    public static function prefixos(): array
    {
        return array_values(array_filter(array_column(self::todos(), 'prefixo')));
    }

    public static function atual(): string
    {
        return app()->getLocale();
    }

    public static function prefixoAtual(): ?string
    {
        return self::todos()[self::atual()]['prefixo'] ?? null;
    }

    /** Idioma indicado pela URL (primeiro segmento) */
    public static function daRequisicao(Request $request): string
    {
        $primeiro = $request->segment(1);

        foreach (self::todos() as $codigo => $dados) {
            if ($dados['prefixo'] && $dados['prefixo'] === $primeiro) {
                return $codigo;
            }
        }

        return self::padrao();
    }

    /**
     * URL da página atual em outro idioma (seletor e hreflang). O prefixo de idioma já foi
     * retirado do caminho da requisição (ver DefinirIdioma), então basta colocar o novo.
     */
    public static function urlEm(string $codigo, ?Request $request = null): string
    {
        $request ??= request();
        $path = trim($request->path(), '/');

        $novo = self::todos()[$codigo]['prefixo'] ?? null;
        $path = trim(($novo ? $novo.'/' : '').$path, '/');

        $url = rtrim($request->root(), '/').'/'.$path;
        // /page/N/ mantém a barra final (formato do WordPress)
        if (preg_match('#/page/\d+$#', $url)) {
            $url .= '/';
        }

        $query = $request->query();

        return $query ? $url.'?'.http_build_query($query) : $url;
    }
}
