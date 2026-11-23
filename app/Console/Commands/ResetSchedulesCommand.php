<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetSchedulesCommand extends Command
{
    protected $signature = 'schedules:reset {--period= : ID Periode spesifik yang ingin dikosongkan}';
    protected $description = 'Mengosongkan seluruh data jadwal kuliah (schedule) dan sesi mingguan (schedule_session) untuk keperluan pengujian import';

    public function handle(): int
    {
        $periodId = $this->option('period');

        if ($periodId) {
            $scheduleIds = DB::table('schedule')->where('period_id', $periodId)->pluck('id');
            DB::table('schedule_session')->whereIn('schedule_id', $scheduleIds)->delete();
            DB::table('schedule')->where('period_id', $periodId)->delete();
            $this->info("Jadwal untuk periode ID {$periodId} berhasil dikosongkan.");
        } else {
            DB::table('schedule_session')->truncate();
            // Nonaktifkan sementara foreign key checks agar truncate tabel schedule bersih
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('schedule')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('Seluruh data tabel schedule dan schedule_session berhasil dikosongkan.');
        }

        return Command::SUCCESS;
    }
}
