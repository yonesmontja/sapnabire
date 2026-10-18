<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SMART Control — Master Bidang.
 * Bidang bisa bertingkat (mis. Kurikulum > Asesmen) lewat parent_id.
 * Seluruh Master Program, KPI, Delegasi, dan Issue nantinya menempel ke bidang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('school_id')->constrained()->cascadeOnDelete();
            $table->ulid('parent_id')->nullable();
            $table->string('code', 20)->comment('Mis. KUR, KES, SAR, HUM, KEU');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable()->comment('Hex, untuk dashboard');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'code']);
        });

        // FK ke diri sendiri harus dibuat setelah primary key ada.
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
