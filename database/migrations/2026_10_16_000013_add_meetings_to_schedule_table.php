<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->unsignedTinyInteger('meeting_from')->nullable()->after('end_time');
            $table->unsignedTinyInteger('meeting_to')->nullable()->after('meeting_from');
        });
    }

    public function down(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->dropColumn(['meeting_from', 'meeting_to']);
        });
    }
};
