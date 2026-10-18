<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master pegawai (guru & tendik). Pengganti sheet legacy `MasterGuru`.
 * Dipisah dari `users`: tidak semua pegawai punya akun, dan akun bisa dicabut tanpa menghapus histori HR.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('employee_number', 30)->nullable()->comment('NIP / NIY');
            $table->string('nuptk', 16)->nullable();
            $table->string('full_name');
            $table->string('front_title', 30)->nullable();
            $table->string('back_title', 50)->nullable();
            $table->char('gender', 1)->comment('L / P');
            $table->string('employment_type', 20)->comment('App\Enums\EmploymentType');
            $table->string('position')->nullable()->comment('Jabatan struktural, mis. Wakasek Kurikulum');
            $table->boolean('is_teacher')->default(true);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('joined_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'is_active']);
        });

        DB::statement('CREATE UNIQUE INDEX employees_school_number_unique ON employees (school_id, employee_number) WHERE employee_number IS NOT NULL AND deleted_at IS NULL');
        DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_valid_gender CHECK (gender IN ('L', 'P'))");

        // Kepala sekolah menunjuk ke pegawai (bukan teks bebas seperti di sheet lama).
        Schema::table('schools', function (Blueprint $table) {
            $table->foreignUlid('principal_employee_id')->nullable()->after('timezone')
                ->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropConstrainedForeignId('principal_employee_id');
        });
        Schema::dropIfExists('employees');
    }
};
