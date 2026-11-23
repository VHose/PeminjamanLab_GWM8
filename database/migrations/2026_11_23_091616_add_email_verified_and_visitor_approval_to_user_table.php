<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('pic_or_instansi', 100)->nullable()->after('phone');
            $table->string('status', 20)->default('active')->after('is_active');
        });

        // Set existing users to active and email verified
        DB::table('user')->update([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'pic_or_instansi', 'status']);
        });
    }
};
