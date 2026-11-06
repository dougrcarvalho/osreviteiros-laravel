<?php

namespace App\Services\Importacao;

use Generator;
use SimpleXMLElement;

/**
 * Lê um arquivo WXR (exportação do WordPress) e devolve cada <item> como array simples.
 * Os valores vêm de CDATA, então entidades (&amp;) chegam literais e são decodificadas aqui.
 */
class WpExportReader
{
    private const NS_WP      = 'http://wordpress.org/export/1.2/';
    private const NS_CONTENT = 'http://purl.org/rss/1.0/modules/content/';

    /**
     * @return Generator<int, array{
     *   post_id:int, tipo:string, status:string, titulo:string, slug:string, conteudo:string,
     *   data:string, meta:array<string,string>, termos:list<array{dominio:string,slug:string,nome:string}>,
     *   anexo_url:?string
     * }>
     */
    public function itens(string $arquivo): Generator
    {
        $xml = simplexml_load_file($arquivo, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET)
            ?: throw new \RuntimeException("XML inválido: {$arquivo}");

        foreach ($xml->channel->item as $item) {
            $wp = $item->children(self::NS_WP);

            $meta = [];
            foreach ($wp->postmeta as $m) {
                $meta[(string) $m->meta_key] = $this->texto((string) $m->meta_value);
            }

            $termos = [];
            foreach ($item->category as $c) {
                $termos[] = [
                    'dominio' => (string) $c['domain'],
                    'slug'    => (string) $c['nicename'],
                    'nome'    => $this->texto((string) $c),
                ];
            }

            yield [
                'post_id'   => (int) $wp->post_id,
                'tipo'      => (string) $wp->post_type,
                'status'    => (string) $wp->status,
                'titulo'    => $this->texto((string) $item->title),
                'slug'      => (string) $wp->post_name,
                'conteudo'  => $this->texto((string) $item->children(self::NS_CONTENT)->encoded),
                'data'      => (string) $wp->post_date,
                'meta'      => $meta,
                'termos'    => $termos,
                'anexo_url' => ((string) $wp->attachment_url) ?: null,
            ];
        }
    }

    private function texto(string $valor): string
    {
        return trim(html_entity_decode($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
