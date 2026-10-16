<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referensi dropdown slot jam saat input jadwal, bukan FK ke schedule
        Schema::create('class_slot', function (Blueprint $table) {
            $table->string('code', 10)->primary(); // contoh: T2-1, T3-1, P2-1
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_slot');
    }
};
