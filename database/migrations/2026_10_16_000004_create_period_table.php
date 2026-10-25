<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name', 30)->unique();
            $table->date('start_date');                 
            $table->date('end_date');                   
            $table->date('uts_start');
            $table->date('uts_end');
            $table->date('uas_start');
            $table->date('uas_end');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE period ADD CONSTRAINT period_dates_chk CHECK (end_date >= start_date AND uts_end >= uts_start AND uas_end >= uas_start)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('period');
    }
};
