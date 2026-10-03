<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_program', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('color_hex', 7);
            $table->timestamps();
        });

        Schema::create('lecturer', function (Blueprint $table) {
            $table->string('nik', 30)->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('capacity');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')->constrained('study_program')->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('period', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('semester', ['odd', 'even']);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('uts_start')->nullable();
            $table->date('uts_end')->nullable();
            $table->date('uas_start')->nullable();
            $table->date('uas_end')->nullable();
            $table->timestamps();
        });

        Schema::create('section', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('course')->restrictOnDelete();
            $table->foreignId('period_id')->constrained('period')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('room')->restrictOnDelete();
            $table->string('lecturer_nik', 30)->nullable();
            $table->string('presenter_name')->nullable();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->foreign('lecturer_nik')->references('nik')->on('lecturer')->restrictOnDelete();
            $table->index(['period_id', 'room_id', 'day_of_week']);
        });

        Schema::create('booking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->restrictOnDelete();
            $table->foreignId('parent_booking_id')->nullable()->constrained('booking')->restrictOnDelete();
            $table->string('requester_name');
            $table->text('purpose');
            $table->unsignedSmallInteger('participant_count');
            $table->enum('type', ['new', 'change']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index(['status', 'start_datetime']);
        });

        Schema::create('booking_room', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('booking')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('room')->restrictOnDelete();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->timestamps();

            $table->index(['room_id', 'start_datetime', 'end_datetime']);
        });

        Schema::create('booking_approval', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('booking')->restrictOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('user')->restrictOnDelete();
            $table->unsignedTinyInteger('level');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'level']);
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('user')->restrictOnDelete();
            $table->string('action', 30);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('booking_approval');
        Schema::dropIfExists('booking_room');
        Schema::dropIfExists('booking');
        Schema::dropIfExists('section');
        Schema::dropIfExists('period');
        Schema::dropIfExists('course');
        Schema::dropIfExists('room');
        Schema::dropIfExists('lecturer');
        Schema::dropIfExists('study_program');
    }
};
