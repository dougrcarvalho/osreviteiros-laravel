<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pedido de cancelamento feito pelo assinante na área do aluno.
        // O plano só muda quando a equipe (ou o webhook do gateway) processa o cancelamento.
        Schema::create('solicitacoes_cancelamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plano_id')->nullable()->constrained('planos')->nullOnDelete(); // plano na data do pedido
            $table->text('motivo')->nullable();
            $table->timestamp('atendida_em')->nullable(); // plano passou a ser grátis (ou o assinante desistiu)
            $table->timestamps();

            $table->index(['user_id', 'atendida_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_cancelamento');
    }
};
