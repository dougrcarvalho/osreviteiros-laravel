<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('funcao', 20)->nullable()->after('plano_id');         // null = cliente | editor | administrador
            $table->string('status', 20)->default('ativo')->after('funcao');     // ativo | bloqueado
            $table->string('bloqueio_motivo')->nullable()->after('status');
            $table->timestamp('bloqueado_em')->nullable()->after('bloqueio_motivo');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            $table->index('funcao');
            $table->index(['status', 'created_at']);
            $table->index('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['funcao']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['last_login_at']);
            $table->dropColumn(['funcao', 'status', 'bloqueio_motivo', 'bloqueado_em', 'last_login_at', 'last_login_ip']);
        });
    }
};
