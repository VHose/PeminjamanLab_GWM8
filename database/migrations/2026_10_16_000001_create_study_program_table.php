<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_program', function (Blueprint $table) {
            // Kode prodi dari sistem universitas dipakai langsung sebagai PK (72 IF, 73 SI, 79 S2, 00 Undefined)
            $table->string('id', 5)->primary();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Baris data default untuk dosen di luar prodi utama (Sipil, Psikologi, dll.)
        DB::table('study_program')->insert([
            'id' => '00',
            'name' => 'Undefined',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('study_program');
    }
};
