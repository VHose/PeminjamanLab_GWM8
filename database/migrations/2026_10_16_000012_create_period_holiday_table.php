<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_holiday', function (Blueprint $table) {
            $table->unsignedSmallInteger('period_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('note', 100)->nullable();

            $table->primary(['period_id', 'start_date']);

            $table->foreign('period_id')->references('id')->on('period')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE period_holiday ADD CONSTRAINT period_holiday_chk CHECK (end_date >= start_date)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('period_holiday');
    }
};
