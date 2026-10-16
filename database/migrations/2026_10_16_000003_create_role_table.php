<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel lookup kecil (6 baris), jadi PK tinyint sudah lebih dari cukup
        Schema::create('role', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 30)->unique(); // Visitor, Staf_Lab, Dosen, Kepala_Prodi, Kepala_Lab, Admin
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role');
    }
};
