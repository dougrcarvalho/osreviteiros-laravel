<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20)->default('familia'); // familia | textura | projeto
            $table->string('nome');
            $table->string('slug')->unique();
            $table->text('descricao')->nullable();
            $table->string('imagem_url')->nullable();
            $table->string('link_download', 2048)->nullable(); // NUNCA expor sem verificação de plano
            $table->unsignedInteger('curtidas')->default(0);
            $table->foreignId('fabricante_id')->nullable()->constrained('fabricantes')->nullOnDelete();
            $table->boolean('ativo')->default(true);
            // Texto desnormalizado: nome + descrição + nomes de todas as categorias + fabricante
            $table->text('busca_texto')->nullable();
            $table->unsignedBigInteger('wp_post_id')->nullable()->index();
            $table->timestamps();

            $table->index(['ativo', 'created_at']);
            $table->index(['ativo', 'tipo']);
            $table->fullText('busca_texto');
        });

        Schema::create('familia_categoria', function (Blueprint $table) {
            $table->foreignId('familia_id')->constrained('familias')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->primary(['familia_id', 'categoria_id']);
            $table->index(['categoria_id', 'familia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('familia_categoria');
        Schema::dropIfExists('familias');
    }
};
