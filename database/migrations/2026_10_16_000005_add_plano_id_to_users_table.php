<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('plano_id')->nullable()->after('email')->constrained('planos')->nullOnDelete();
            $table->unsignedBigInteger('wp_user_id')->nullable()->after('plano_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plano_id');
            $table->dropColumn('wp_user_id');
        });
    }
};
