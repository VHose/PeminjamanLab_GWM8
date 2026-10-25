<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Many-to-many user x role x period. Satu user boleh pegang banyak role,
        // dan role bisa beda tiap periode (staf periode ini, bukan staf periode depan).
        Schema::create('user_role', function (Blueprint $table) {
            $table->string('user_id', 10);
            $table->unsignedTinyInteger('role_id');
            $table->unsignedSmallInteger('period_id')->nullable();

            $table->unique(['user_id', 'role_id', 'period_id']);
            $table->index(['period_id', 'role_id']); // "siapa saja Kepala Lab periode ini"
            $table->index(['role_id', 'period_id']);

            $table->foreign('user_id')->references('id')->on('user')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('role')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('period_id')->references('id')->on('period')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role');
    }
};
