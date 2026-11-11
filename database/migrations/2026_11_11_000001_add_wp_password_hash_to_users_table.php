<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hash de senha do WordPress (phpass $P$ ou $wp$2y$): convertido para bcrypt no 1º login e apagado
            $table->string('wp_password_hash', 255)->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wp_password_hash');
        });
    }
};
