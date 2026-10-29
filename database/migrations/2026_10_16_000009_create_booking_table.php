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
            $table->string('pic_name', 100);
            $table->string('pic_phone', 20);
            $table->string('purpose', 500);                          // dibatasi, dokumen panjang lewat attachment
            // 0 PENDING_PRODI, 1 PENDING_LAB, 2 APPROVED, 3 REJECTED, 4 CANCELLED, 5 CHANGE_REQUESTED
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('decision_note', 255)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['status', 'created_at']);

            $table->foreign('user_id')->references('id')->on('user')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        // Many-to-many booking x room. PK komposit: booking_id + booking_date + start_time.
        // room_id sebagai foreign key biasa.
        Schema::create('booking_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id');
            $table->string('room_id', 10);
            $table->date('booking_date');                            // Senin sampai Sabtu, Minggu ditolak CHECK
            $table->time('start_time');                              // dibulatkan ke :00 / :30 oleh aplikasi
            $table->time('end_time');
            // 0 PENDING, 1 APPROVED, 2 REJECTED, 3 CANCELLED
            $table->unsignedTinyInteger('status')->default(0);

            $table->primary(['booking_id', 'booking_date', 'start_time']);
            // Index utama cek bentrok: ruangan + tanggal + jam
            $table->index(['room_id', 'booking_date', 'start_time', 'end_time'], 'booking_detail_conflict_idx');

            $table->foreign('booking_id')->references('id')->on('booking')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('room')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        // Weak entity bergantung ke booking (identifying relationship).
        // PK komposit: booking_id + original_name.
        Schema::create('booking_attachment', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id');
            $table->string('original_name', 100);
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['booking_id', 'original_name']);
            $table->foreign('booking_id')->references('id')->on('booking')
                ->cascadeOnUpdate()->cascadeOnDelete();
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
