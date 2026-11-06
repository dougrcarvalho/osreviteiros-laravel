<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Taxonomias configuráveis por tipo de item.
 *
 * Antes: categorias.nivel (1–4) fixo para tudo. Agora: cada tipo (familia,
 * textura, projeto) declara suas taxonomias em `taxonomias`, e cada termo
 * (`categorias`) pertence a uma delas. A marca deixa de ser termo e passa a
 * usar o Fabricante (familias.fabricante_id); "acessar-gratis" vira familias.gratuita.
 */
return new class extends Migration
{
    private const FAMILIA = [
        // nível, slug, nome, plural, fonte, wp, nível do pai
        [1, 'cat', 'Categoria', 'Categorias', 'termos', 'categorias', null],
        [2, 'sub', 'Subcategoria', 'Subcategorias', 'termos', 'subcategorias', 1],
        [3, 'subcl', 'Subclassificação', 'Subclassificações', 'termos', 'subclassificacao', 2],
        [4, 'marca', 'Marca', 'Marcas', 'fabricante', 'selecionar-marca', 1],
    ];

    public function up(): void
    {
        Schema::create('taxonomias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_item', 20);                 // familia | textura | projeto
            $table->string('slug', 50);                      // parâmetro da URL: ?cat=…
            $table->string('nome');
            $table->string('nome_plural');
            $table->foreignId('pai_id')->nullable()->constrained('taxonomias')->nullOnDelete(); // só aparece depois do pai
            $table->string('fonte', 20)->default('termos');  // termos | fabricante
            $table->integer('ordem')->default(0);
            $table->boolean('ativa')->default(true);
            $table->string('wp_taxonomia')->nullable();
            $table->timestamps();

            $table->unique(['tipo_item', 'slug']);
            $table->index(['tipo_item', 'ordem']);
        });

        Schema::table('familias', function (Blueprint $table) {
            $table->boolean('gratuita')->default(false)->after('ativo')->index();
        });

        // Taxonomias de Famílias = os 4 níveis antigos
        $ids = [];
        foreach (self::FAMILIA as [$nivel, $slug, $nome, $plural, $fonte, $wp, $paiNivel]) {
            $ids[$nivel] = DB::table('taxonomias')->insertGetId([
                'tipo_item'    => 'familia',
                'slug'         => $slug,
                'nome'         => $nome,
                'nome_plural'  => $plural,
                'pai_id'       => $paiNivel ? $ids[$paiNivel] : null,
                'fonte'        => $fonte,
                'ordem'        => $nivel,
                'wp_taxonomia' => $wp,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // Marca passa a ser só o fabricante
        Schema::table('fabricantes', fn (Blueprint $table) => $table->dropConstrainedForeignId('categoria_id'));

        Schema::table('categorias', function (Blueprint $table) {
            $table->unsignedBigInteger('taxonomia_id')->nullable()->after('id');
        });

        // "acessar-gratis" → familias.gratuita
        $gratis = DB::table('categorias')->where('nivel', 1)->where('slug', 'acessar-gratis')->value('id');
        if ($gratis) {
            DB::table('familias')
                ->whereIn('id', DB::table('familia_categoria')->where('categoria_id', $gratis)->select('familia_id'))
                ->update(['gratuita' => true]);
            DB::table('categorias')->where('id', $gratis)->delete();
        }

        DB::table('categorias')->where('nivel', 4)->delete();

        foreach ([1, 2, 3] as $nivel) {
            DB::table('categorias')->where('nivel', $nivel)->update(['taxonomia_id' => $ids[$nivel]]);
        }
        DB::table('categorias')->whereNull('taxonomia_id')->delete();

        Schema::table('categorias', function (Blueprint $table) {
            $table->dropUnique(['taxonomia', 'slug']);
            $table->dropIndex(['nivel', 'parent_id']);
            $table->dropColumn(['nivel', 'taxonomia']);
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->unsignedBigInteger('taxonomia_id')->nullable(false)->change();
            $table->foreign('taxonomia_id')->references('id')->on('taxonomias')->cascadeOnDelete();
            $table->unique(['taxonomia_id', 'slug']);
            $table->index(['taxonomia_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropForeign(['taxonomia_id']);
            $table->dropUnique(['taxonomia_id', 'slug']);
            $table->dropIndex(['taxonomia_id', 'ordem']);
            $table->tinyInteger('nivel')->default(1);
            $table->string('taxonomia')->default('categorias');
        });

        DB::table('categorias')->update([
            'nivel'     => DB::raw('(select ordem from taxonomias where taxonomias.id = categorias.taxonomia_id)'),
            'taxonomia' => DB::raw('(select coalesce(wp_taxonomia, slug) from taxonomias where taxonomias.id = categorias.taxonomia_id)'),
        ]);

        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn('taxonomia_id');
            $table->unique(['taxonomia', 'slug']);
            $table->index(['nivel', 'parent_id']);
        });

        Schema::table('fabricantes', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->unique()->constrained('categorias')->nullOnDelete();
        });

        Schema::table('familias', fn (Blueprint $table) => $table->dropColumn('gratuita'));
        Schema::dropIfExists('taxonomias');
    }
};
