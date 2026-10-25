<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('schedule', 'meeting_from')) {
            Schema::table('schedule', function (Blueprint $table) {
                $table->dropColumn(['meeting_from', 'meeting_to']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->dropColumn(['meeting_from', 'meeting_to']);
        });
    }
};
