<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tahun ajaran & semester. Pengganti sheet legacy `MasterPeriode`.
 * Fondasi untuk Prota/Promes (minggu efektif) dan periode program Control Center.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 9)->comment('Contoh: 2026/2027');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('term')->comment('1 = Ganjil, 2 = Genap');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['academic_year_id', 'term']);
        });

        // Hanya boleh satu tahun ajaran aktif per sekolah.
        DB::statement('CREATE UNIQUE INDEX academic_years_one_active_per_school ON academic_years (school_id) WHERE is_active');
        DB::statement('ALTER TABLE academic_years ADD CONSTRAINT academic_years_valid_range CHECK (ends_on > starts_on)');
        DB::statement('ALTER TABLE semesters ADD CONSTRAINT semesters_valid_term CHECK (term IN (1, 2))');
        DB::statement('ALTER TABLE semesters ADD CONSTRAINT semesters_valid_range CHECK (ends_on > starts_on)');
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
    }
};
