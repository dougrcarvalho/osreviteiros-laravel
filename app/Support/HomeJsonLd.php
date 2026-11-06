<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Dados estruturados da home, gerados a partir dos mesmos dados que a página exibe */
class HomeJsonLd
{
    public static function montar(array $dados, string $descricao): array
    {
        $home = route('home');
        $org = $home.'#organizacao';

        $faq = array_values(array_filter($dados['faq'], fn (array $f) => ! Str::startsWith($f['resposta'], '[')));

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $org,
                    'name' => 'Os Reviteiros',
                    'url' => $home,
                    'logo' => asset('images/logo/logo-horizontal.png'), // PNG: o Google não aceita SVG como logo
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $home.'#site',
                    'name' => 'Os Reviteiros',
                    'url' => $home,
                    'inLanguage' => 'pt-BR',
                    'publisher' => ['@id' => $org],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => route('familias.index').'?busca={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@type' => 'SoftwareApplication',
                    'name' => 'Plugin Os Reviteiros para Revit',
                    'description' => $descricao,
                    'applicationCategory' => 'DesignApplication',
                    'operatingSystem' => 'Windows',
                    'publisher' => ['@id' => $org],
                    'offers' => array_map(fn (array $p) => [
                        '@type' => 'Offer',
                        'name' => $p['nome'],
                        'description' => $p['descricao'],
                        'price' => number_format($p['preco'], 2, '.', ''),
                        'priceCurrency' => 'BRL',
                        'url' => $home.'#planos',
                    ], $dados['planos']),
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(fn (array $f) => [
                        '@type' => 'Question',
                        'name' => $f['pergunta'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['resposta']],
                    ], $faq),
                ],
            ],
        ];
    }
}
