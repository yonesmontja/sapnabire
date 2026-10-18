<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUlid('school_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('username', 50)->unique()->after('name');
            $table->string('role', 30)->default('teacher')->after('password')->comment('App\Enums\UserRole');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            // Guru di sekolah daerah sering belum punya email; login utama memakai username.
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['username', 'role', 'is_active', 'last_login_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
