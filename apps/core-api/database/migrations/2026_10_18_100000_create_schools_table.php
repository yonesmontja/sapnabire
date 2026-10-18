<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SMART Control — Setup Sekolah.
 * Tenant root: setiap data operasional menempel ke satu sekolah (yayasan bisa punya banyak sekolah).
 * Pengganti sheet legacy `PengaturanSekolah`. API key AI TIDAK disimpan di sini (pakai secret manager / .env Django).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('npsn', 8)->unique();
            $table->string('name');
            $table->string('short_name', 50)->nullable();
            $table->string('level', 10)->comment('SD, SMP, SMA, SMK');
            $table->string('accreditation', 2)->nullable();
            $table->string('foundation_name')->nullable()->comment('Nama yayasan');
            $table->text('address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('timezone', 40)->default('Asia/Jayapura');
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            // KKTP default, format rapor, penandatangan, dsb. Struktur divalidasi di FormRequest.
            $table->jsonb('settings')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
