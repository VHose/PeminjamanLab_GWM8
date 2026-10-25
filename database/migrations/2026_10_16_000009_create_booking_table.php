<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 10);                           // peminjam
            $table->string('created_by', 10)->nullable();            // staf yang input atas nama dosen
            $table->string('pic_name', 100);
            $table->string('pic_phone', 20);
            $table->string('purpose', 500);                          // dibatasi, dokumen panjang lewat attachment
            // 0 PENDING_PRODI, 1 PENDING_LAB, 2 APPROVED, 3 REJECTED, 4 CANCELLED, 5 CHANGE_REQUESTED
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('decision_note', 255)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_by');
            $table->index(['status', 'created_at']);

            $table->foreign('user_id')->references('id')->on('user')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('user')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        // Many-to-many booking x room. Satu ruangan boleh muncul beberapa kali di booking
        // yang sama selama tanggal / jamnya beda, jadi tanggal dan jam masuk PK.
        Schema::create('booking_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id');
            $table->string('room_id', 10);
            $table->date('booking_date');                            // Senin sampai Sabtu, Minggu ditolak CHECK
            $table->time('start_time');                              // dibulatkan ke :00 / :30 oleh aplikasi
            $table->time('end_time');
            // 0 PENDING, 1 APPROVED, 2 REJECTED, 3 CANCELLED
            $table->unsignedTinyInteger('status')->default(0);

            $table->primary(['booking_id', 'room_id', 'booking_date', 'start_time']);
            // Index utama cek bentrok: ruangan + tanggal + jam
            $table->index(['room_id', 'booking_date', 'start_time', 'end_time'], 'booking_detail_conflict_idx');

            $table->foreign('booking_id')->references('id')->on('booking')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('room')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('booking_attachment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('booking')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('original_name', 150);
            $table->string('path', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');                  
            $table->timestamp('created_at')->useCurrent();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE booking_detail ADD CONSTRAINT booking_detail_time_chk CHECK (end_time > start_time AND WEEKDAY(booking_date) <= 5)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_attachment');
        Schema::dropIfExists('booking_detail');
        Schema::dropIfExists('booking');
    }
};
