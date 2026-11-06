<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug');
            $table->foreignId('parent_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->tinyInteger('nivel')->default(1); // 1=categoria, 2=subcategoria, 3=subclassificacao, 4=marca
            $table->string('taxonomia');              // mirror do WP: categorias|subcategorias|subclassificacao|selecionar-marca
            $table->integer('ordem')->default(0);
            $table->unsignedBigInteger('wp_term_id')->nullable()->index();
            $table->timestamps();

            // No WP o slug é único por taxonomia, não globalmente
            $table->unique(['taxonomia', 'slug']);
            $table->index(['nivel', 'parent_id']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
