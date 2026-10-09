<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100)->nullable();
            $table->integer('capacity')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')->constrained('study_program')->restrictOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('period', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('uts_start')->nullable();
            $table->date('uts_end')->nullable();
            $table->date('uas_start')->nullable();
            $table->date('uas_end')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('section', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('course')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('room')->restrictOnDelete();
            $table->foreignId('period_id')->constrained('period')->restrictOnDelete();
            $table->string('lecturer_nik', 30)->nullable();
            $table->string('class_code', 10);
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('schedule_type')->default(0);
            $table->integer('quota')->nullable();
            $table->timestamps();

            $table->foreign('lecturer_nik')->references('id')->on('user')->nullOnDelete();
            $table->index(['period_id', 'room_id', 'day_of_week']);
        });

        Schema::create('booking', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 30);
            $table->foreignId('parent_booking_id')->nullable()->constrained('booking')->restrictOnDelete();
            $table->string('requester_name', 100);
            $table->text('purpose');
            $table->unsignedTinyInteger('type');
            $table->unsignedTinyInteger('status');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('user')->restrictOnDelete();
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('booking_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('booking')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('room')->restrictOnDelete();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->unsignedTinyInteger('status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'start_datetime', 'end_datetime']);
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 30)->nullable();
            $table->string('action', 50);
            $table->string('entity_type', 50);
            $table->string('entity_id', 30);
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')->references('id')->on('user')->nullOnDelete();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('booking_detail');
        Schema::dropIfExists('booking');
        Schema::dropIfExists('section');
        Schema::dropIfExists('period');
        Schema::dropIfExists('course');
        Schema::dropIfExists('room');
    }
};
