<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course', function (Blueprint $table) {
            $table->string('id', 15)->primary(); // kode mata kuliah (contoh INF001202672, 12 karakter), langsung PK
            $table->string('study_program_id', 5);
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('study_program_id');
            $table->foreign('study_program_id')->references('id')->on('study_program')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course');
    }
};
