<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Log tidak pernah di-purge. PK = created_at dengan presisi mikrodetik (sesuai arahan dosen),
        // jadi tidak ada kolom id. Isi created_at manual dengan now() baru di setiap insert.
        Schema::create('activity_log', function (Blueprint $table) {
            $table->dateTime('created_at', 6);
            $table->string('user_id', 10)->nullable();               // NULL = aksi sistem
            $table->string('action', 30);                            // created, approved, rejected, cancelled, ...
            $table->string('subject_type', 30);                      // booking, booking_detail, schedule, ...
            $table->string('subject_id', 30);
            $table->string('description', 255)->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->primary('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);

            $table->foreign('user_id')->references('id')->on('user')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
