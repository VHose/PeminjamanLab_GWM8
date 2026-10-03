<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('id');
            $table->boolean('active')->default(true)->after('description');
        });

        DB::table('room')->orderBy('id')->get()->each(fn (object $room) => DB::table('room')->where('id', $room->id)->update(['code' => "LEGACY-{$room->id}"]));

        Schema::table('room', function (Blueprint $table) {
            $table->dropUnique('room_name_unique');
            $table->string('code', 20)->nullable(false)->change();
            $table->string('name', 100)->nullable()->change();
            $table->integer('capacity')->nullable()->change();
        });

        Schema::table('booking', function (Blueprint $table) {
            $table->dropColumn(['start_datetime', 'end_datetime']);
        });
    }

    public function down(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            $table->dateTime('start_datetime')->nullable()->after('status');
            $table->dateTime('end_datetime')->nullable()->after('start_datetime');
        });

        Schema::table('room', function (Blueprint $table) {
            $table->dropColumn(['active', 'code']);
            $table->string('name')->nullable(false)->unique()->change();
            $table->unsignedSmallInteger('capacity')->nullable(false)->change();
        });
    }
};
