<?php

namespace Database\Seeders;

use App\Enums\FonteTaxonomia;
use App\Enums\TipoItem;
use App\Models\Taxonomia;
use Illuminate\Database\Seeder;

/**
 * Taxonomias padrão de cada biblioteca (roda também em produção).
 * Só cria o que falta: não sobrescreve ajustes feitos no painel.
 * Novas classificações (ex.: "cor") são criadas em Administração › Taxonomias.
 */
class TaxonomiaSeeder extends Seeder
{
    /** slug, nome, plural, slug do pai, fonte, taxonomia no WordPress */
    private const PADRAO = [
        'familia' => [
            ['cat', 'Categoria', 'Categorias', null, 'termos', 'categorias'],
            ['sub', 'Subcategoria', 'Subcategorias', 'cat', 'termos', 'subcategorias'],
            ['subcl', 'Subclassificação', 'Subclassificações', 'sub', 'termos', 'subclassificacao'],
            ['marca', 'Marca', 'Marcas', 'cat', 'fabricante', 'selecionar-marca'],
        ],
        'textura' => [
            ['cat', 'Categoria', 'Categorias', null, 'termos', 'categoria-de-texturas'],
            ['marca', 'Marca', 'Marcas', 'cat', 'fabricante', 'marca-de-texturas'],
        ],
        'projeto' => [
            ['cat', 'Categoria', 'Categorias', null, 'termos', 'selecione-o-projeto'],
            ['tipo', 'Tipo', 'Tipos', null, 'termos', 'selecione-o-tipo-do-projeto'],
        ],
    ];

    public function run(): void
    {
        foreach (self::PADRAO as $tipo => $taxonomias) {
            $ids = [];

            foreach ($taxonomias as $ordem => [$slug, $nome, $plural, $pai, $fonte, $wp]) {
                $taxonomia = Taxonomia::firstOrCreate(
                    ['tipo_item' => TipoItem::from($tipo)->value, 'slug' => $slug],
                    [
                        'nome'         => $nome,
                        'nome_plural'  => $plural,
                        'pai_id'       => $pai ? $ids[$pai] : null,
                        'fonte'        => FonteTaxonomia::from($fonte)->value,
                        'ordem'        => $ordem + 1,
                        'wp_taxonomia' => $wp,
                    ],
                );

                // Bases já criadas antes do vínculo com o WordPress: completa só se estiver vazio
                if ($wp && ! $taxonomia->wp_taxonomia) {
                    $taxonomia->update(['wp_taxonomia' => $wp]);
                }

                $ids[$slug] = $taxonomia->id;
            }
        }
    }
}
