<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lista separada por vírgula; entra em busca_texto (famílias e texturas)
        Schema::table('familias', function (Blueprint $table) {
            $table->text('palavras_chave')->nullable()->after('descricao');
        });
    }

    public function down(): void
    {
        Schema::table('familias', function (Blueprint $table) {
            $table->dropColumn('palavras_chave');
        });
    }
};
