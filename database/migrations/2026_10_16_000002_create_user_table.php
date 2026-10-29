<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            // NIK dosen (6) / NRP mahasiswa (10) / kode eksternal (maks 10, di-generate aplikasi)
            $table->string('id', 10)->primary();
            // Foreign key prodi TIDAK boleh null, default '00' (Undefined) untuk dosen luar prodi / pihak luar
            $table->string('study_program_id', 5)->default('00');
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('password', 100);
            $table->rememberToken();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('study_program_id');
            $table->foreign('study_program_id')->references('id')->on('study_program')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 100)->primary();
            $table->string('token', 65);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('user_id', 10)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->longText('payload');
            $table->unsignedInteger('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('user');
    }
};
