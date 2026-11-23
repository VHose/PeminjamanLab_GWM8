<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            if (! Schema::hasColumn('booking', 'parent_booking_id')) {
                $table->unsignedBigInteger('parent_booking_id')->nullable()->after('user_id');
                $table->foreign('parent_booking_id')->references('id')->on('booking')->nullOnDelete();
            }
            if (! Schema::hasColumn('booking', 'type')) {
                $table->unsignedTinyInteger('type')->default(0)->after('parent_booking_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            if (Schema::hasColumn('booking', 'parent_booking_id')) {
                $table->dropForeign(['parent_booking_id']);
                $table->dropColumn('parent_booking_id');
            }
            if (Schema::hasColumn('booking', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
