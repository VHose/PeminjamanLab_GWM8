<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_program', function (Blueprint $table) {
            // Kode prodi dari sistem universitas dipakai langsung sebagai PK (72 IF, 73 SI, 79 S2)
            $table->string('id', 5)->primary();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_program');
    }
};
