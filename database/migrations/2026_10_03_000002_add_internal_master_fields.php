<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_program', fn (Blueprint $table) => $table->boolean('active')->default(true)->after('color_hex'));
        Schema::table('lecturer', function (Blueprint $table) {
            $table->string('lecturer_code')->nullable()->unique()->after('nik');
            $table->string('email')->nullable()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->boolean('active')->default(true)->after('phone');
        });
        Schema::table('course', fn (Blueprint $table) => $table->boolean('active')->default(true)->after('name'));
        Schema::table('period', fn (Blueprint $table) => $table->boolean('active')->default(true)->after('uas_end'));
        Schema::table('section', function (Blueprint $table) {
            $table->string('class_code')->nullable()->after('presenter_name');
            $table->string('class_type')->nullable()->after('class_code');
        });
    }

    public function down(): void
    {
        Schema::table('section', fn (Blueprint $table) => $table->dropColumn(['class_code', 'class_type']));
        Schema::table('period', fn (Blueprint $table) => $table->dropColumn('active'));
        Schema::table('course', fn (Blueprint $table) => $table->dropColumn('active'));
        Schema::table('lecturer', function (Blueprint $table) {
            $table->dropUnique('lecturer_lecturer_code_unique');
            $table->dropColumn(['lecturer_code', 'email', 'phone', 'active']);
        });
        Schema::table('study_program', fn (Blueprint $table) => $table->dropColumn('active'));
    }
};
