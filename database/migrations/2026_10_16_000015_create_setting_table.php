<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel setting sistem (key PK, value, updated_at, tanpa id)
        Schema::create('setting', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->string('value', 255)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting');
    }
};
