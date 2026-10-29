<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jadwal kuliah fix (hasil upload satu file dari TU).
        // Identitas aslinya tetap (period, course, class_code, type, day_of_week) lewat UNIQUE.
        // Surrogate id dipakai karena tabel ini jadi parent schedule_sessions (puluhan ribu baris),
        // supaya FK anaknya cukup 1 kolom, bukan 5 kolom.
        Schema::create('schedule', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('period_id');
            $table->string('course_id', 15);
            $table->string('class_code', 5);
            $table->unsignedTinyInteger('type')->default(0);        // 0 teori, 1 praktikum
            $table->unsignedTinyInteger('day_of_week');             // 1 Senin ... 5 Jumat (kuliah tidak ada hari Sabtu)
            $table->string('user_id', 10);                           // dosen pengampu
            $table->string('room_id', 10);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('notes', 100)->nullable();                // keterangan jadwal (kelas gabungan, dll.)
            $table->string('sks', 10);                   // total beban SKS (misal "4" atau "3+1")
            $table->timestamps();

            $table->unique(['period_id', 'course_id', 'class_code', 'type', 'day_of_week'], 'schedule_natural_unique');
            $table->index('course_id');
            $table->index('user_id');
            $table->index(['room_id', 'day_of_week', 'start_time']);

            $table->foreign('period_id')->references('id')->on('period')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('course_id')->references('id')->on('course')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('user')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('room_id')->references('id')->on('room')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        // Hasil generate per minggu (default semua aktif, staf tinggal matikan 1 baris kalau libur)
        // Nilai room, start_time, end_time NOT NULL (diisi default dari schedule induk)
        Schema::create('schedule_session', function (Blueprint $table) {
            $table->unsignedInteger('schedule_id');
            $table->unsignedTinyInteger('week_no');                  // pertemuan ke 1..14 (UTS/UAS tidak punya baris)
            $table->date('session_date');
            $table->boolean('is_active')->default(true);
            $table->string('room_id', 10);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('note', 100)->nullable();

            $table->primary(['schedule_id', 'week_no']);
            $table->index(['session_date', 'is_active']);
            $table->index('room_id');

            $table->foreign('schedule_id')->references('id')->on('schedule')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('room')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE schedule ADD CONSTRAINT schedule_chk CHECK (day_of_week BETWEEN 1 AND 5 AND type IN (0, 1) AND end_time > start_time)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_session');
        Schema::dropIfExists('schedule');
    }
};
