<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isMysql = in_array(DB::getDriverName(), ['mysql', 'mariadb']);

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        // 1. study_program
        Schema::table('study_program', function (Blueprint $table) {
            $table->string('name', 50)->change();
            if (Schema::hasColumn('study_program', 'color_hex')) {
                $table->char('color_hex', 7)->default('#E2E3E5')->change();
            }
        });

        // 2. role
        Schema::table('role', function (Blueprint $table) {
            $table->string('name', 15)->change();
        });

        // 3. user
        Schema::table('user', function (Blueprint $table) {
            $table->string('name', 100)->change();
            $table->string('email', 70)->change();
            $table->string('phone', 15)->nullable()->change();
            $table->string('password', 100)->change();
        });

        // 4. period
        Schema::table('period', function (Blueprint $table) {
            $table->string('name', 20)->change();
        });

        // 5. course
        Schema::table('course', function (Blueprint $table) {
            $table->string('id', 12)->change();
            $table->string('name', 60)->change();
        });

        // 6. room
        Schema::table('room', function (Blueprint $table) {
            $table->string('id', 8)->change();
            $table->string('name', 25)->change();
        });

        // 7. schedule
        Schema::table('schedule', function (Blueprint $table) {
            $table->string('course_id', 12)->change();
            $table->string('class_code', 3)->change();
            $table->string('room_id', 8)->change();
        });

        // 8. schedule_session
        Schema::table('schedule_session', function (Blueprint $table) {
            $table->string('room_id', 8)->change();
            $table->string('note', 100)->nullable()->change();
        });

        // 9. booking
        Schema::table('booking', function (Blueprint $table) {
            $table->string('pic_name', 70)->change();
            $table->string('pic_phone', 15)->change();
            $table->string('purpose', 255)->change();
            $table->string('decision_note', 150)->nullable()->change();
        });

        // 10. booking_detail
        Schema::table('booking_detail', function (Blueprint $table) {
            $table->string('room_id', 8)->change();
        });

        // 11. booking_attachment
        Schema::table('booking_attachment', function (Blueprint $table) {
            $table->string('original_name', 100)->change();
            if (Schema::hasColumn('booking_attachment', 'path')) {
                $table->string('path', 150)->change();
            }
            if (Schema::hasColumn('booking_attachment', 'mime_type')) {
                $table->string('mime_type', 50)->change();
            }
        });

        // 12. period_holiday
        Schema::table('period_holiday', function (Blueprint $table) {
            $table->string('note', 60)->nullable()->change();
        });

        // 13. activity_log
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('action', 20)->change();
            $table->string('subject_type', 20)->change();
            $table->string('subject_id', 20)->change();
        });

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function down(): void
    {
        $isMysql = in_array(DB::getDriverName(), ['mysql', 'mariadb']);

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        Schema::table('study_program', function (Blueprint $table) {
            $table->string('name', 100)->change();
            if (Schema::hasColumn('study_program', 'color_hex')) {
                $table->string('color_hex', 7)->default('#E2E3E5')->change();
            }
        });

        Schema::table('role', function (Blueprint $table) {
            $table->string('name', 30)->change();
        });

        Schema::table('user', function (Blueprint $table) {
            $table->string('name', 100)->change();
            $table->string('email', 100)->change();
            $table->string('phone', 20)->nullable()->change();
            $table->string('password', 65)->change();
        });

        Schema::table('period', function (Blueprint $table) {
            $table->string('name', 30)->change();
        });

        Schema::table('course', function (Blueprint $table) {
            $table->string('id', 15)->change();
            $table->string('name', 100)->change();
        });

        Schema::table('room', function (Blueprint $table) {
            $table->string('id', 10)->change();
            $table->string('name', 50)->change();
        });

        Schema::table('schedule', function (Blueprint $table) {
            $table->string('course_id', 15)->change();
            $table->string('class_code', 5)->change();
            $table->string('room_id', 10)->change();
        });

        Schema::table('schedule_session', function (Blueprint $table) {
            $table->string('room_id', 10)->nullable()->change();
            $table->string('note', 150)->nullable()->change();
        });

        Schema::table('booking', function (Blueprint $table) {
            $table->string('pic_name', 100)->change();
            $table->string('pic_phone', 20)->change();
            $table->string('purpose', 500)->change();
            $table->string('decision_note', 255)->nullable()->change();
        });

        Schema::table('booking_detail', function (Blueprint $table) {
            $table->string('room_id', 10)->change();
        });

        Schema::table('booking_attachment', function (Blueprint $table) {
            $table->string('original_name', 100)->change();
            if (Schema::hasColumn('booking_attachment', 'path')) {
                $table->string('path', 255)->change();
            }
            if (Schema::hasColumn('booking_attachment', 'mime_type')) {
                $table->string('mime_type', 100)->change();
            }
        });

        Schema::table('period_holiday', function (Blueprint $table) {
            $table->string('note', 100)->nullable()->change();
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('action', 30)->change();
            $table->string('subject_type', 30)->change();
            $table->string('subject_id', 30)->change();
        });

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
