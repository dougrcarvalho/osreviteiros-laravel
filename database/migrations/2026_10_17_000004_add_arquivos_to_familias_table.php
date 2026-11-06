<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('familias', function (Blueprint $table) {
            // Preenchidos quando o arquivo foi enviado pelo painel (null = link externo)
            $table->string('imagem_path')->nullable()->after('imagem_url');
            $table->string('arquivo_disk', 30)->nullable()->after('link_download');
            $table->string('arquivo_path')->nullable()->unique()->after('arquivo_disk');
            $table->string('arquivo_nome')->nullable()->after('arquivo_path');
            $table->unsignedBigInteger('arquivo_tamanho')->nullable()->after('arquivo_nome');
        });

        Schema::table('fabricantes', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('familias', function (Blueprint $table) {
            $table->dropUnique(['arquivo_path']);
            $table->dropColumn(['imagem_path', 'arquivo_disk', 'arquivo_path', 'arquivo_nome', 'arquivo_tamanho']);
        });
        Schema::table('fabricantes', fn (Blueprint $table) => $table->dropColumn('logo_path'));
    }
};
