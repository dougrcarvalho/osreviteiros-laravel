<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plano_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plano_anterior_id')->nullable()->constrained('planos')->nullOnDelete();
            $table->foreignId('plano_novo_id')->nullable()->constrained('planos')->nullOnDelete();
            $table->string('origem', 20);    // cadastro | manual | hotmart | eduzz | importacao
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete(); // funcionário (origem manual)
            $table->string('referencia')->nullable(); // id da transação no gateway
            $table->text('observacao')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plano_historicos');
    }
};
