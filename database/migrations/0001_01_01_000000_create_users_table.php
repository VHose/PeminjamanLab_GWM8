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
            $table->string('code', 30)->nullable()->unique();
            $table->string('name', 100)->unique();
            $table->string('color_hex', 7);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('role', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->timestamps();
        });

        Schema::create('user', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->foreignId('study_program_id')->nullable()->constrained('study_program')->nullOnDelete();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 30);
            $table->foreignId('role_id')->constrained('role')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('user')->restrictOnDelete();
            $table->index(['user_id', 'role_id']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id', 30)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('user');
        Schema::dropIfExists('role');
        Schema::dropIfExists('study_program');
    }
};
