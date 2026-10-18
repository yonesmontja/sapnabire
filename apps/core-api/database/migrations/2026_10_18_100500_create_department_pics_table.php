<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SMART Control — Master PIC.
 * Penugasan pegawai ke bidang per tahun ajaran, lengkap dengan peran RACI.
 * Histori tidak dihapus: penugasan diakhiri dengan `ended_on`, sehingga laporan tahun lalu tetap akurat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_pics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('department_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->comment('App\Enums\PicRole');
            $table->char('raci', 1)->comment('R / A / C / I — default Matriks RACI');
            $table->string('decree_number')->nullable()->comment('Nomor SK penugasan');
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'academic_year_id']);
        });

        // Satu bidang hanya boleh punya satu PIC utama aktif per tahun ajaran.
        DB::statement("CREATE UNIQUE INDEX department_pics_one_lead ON department_pics (department_id, academic_year_id) WHERE role = 'lead' AND ended_on IS NULL");
        // Pegawai tidak boleh ditugaskan ganda (aktif) di bidang & tahun yang sama.
        DB::statement('CREATE UNIQUE INDEX department_pics_no_duplicate ON department_pics (department_id, employee_id, academic_year_id) WHERE ended_on IS NULL');
        DB::statement("ALTER TABLE department_pics ADD CONSTRAINT department_pics_valid_raci CHECK (raci IN ('R', 'A', 'C', 'I'))");
        DB::statement('ALTER TABLE department_pics ADD CONSTRAINT department_pics_valid_range CHECK (ended_on IS NULL OR ended_on >= started_on)');
    }

    public function down(): void
    {
        Schema::dropIfExists('department_pics');
    }
};
