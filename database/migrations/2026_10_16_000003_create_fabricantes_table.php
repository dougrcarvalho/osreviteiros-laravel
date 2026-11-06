<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabricantes', function (Blueprint $table) {
            $table->id();
            // Termo "selecionar-marca" (nível 4) correspondente — fonte do filtro ?marca=
            $table->foreignId('categoria_id')->nullable()->unique()->constrained('categorias')->nullOnDelete();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->text('descricao')->nullable();
            $table->string('site_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabricantes');
    }
};
