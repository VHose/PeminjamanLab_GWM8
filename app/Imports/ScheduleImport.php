<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\Period;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Role;
use App\Services\PeriodWeekCalendar;
use App\Services\ScheduleSessionGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class ScheduleImport implements ToCollection
{
    protected Period $period;
    protected int $importedCount = 0;
    protected int $skippedCount = 0;

    public function __construct(Period $period)
    {
        $this->period = $period;
    }

    public function collection(Collection $rows): void
    {
        $now = now();
        $dosenRoleId = Role::where('name', 'Dosen')->value('id');
        $validProdiIds = StudyProgram::pluck('id')->all();

        $roomMapping = [
            'lab adv 1' => 'ADV1',
            'lab adv 2' => 'ADV2',
            'lab adv 3' => 'ADV3',
            'lab adv 4' => 'ADV4',
            'lab adv1' => 'ADV1',
            'lab adv2' => 'ADV2',
            'lab adv3' => 'ADV3',
            'lab adv4' => 'ADV4',
            'lab prog 1' => 'PROG1',
            'lab prog 2' => 'PROG2',
            'lab prog1' => 'PROG1',
            'lab prog2' => 'PROG2',
            'lab database' => 'DB',
            'lab db' => 'DB',
            'lab enterprise 1' => 'ENT1',
            'lab enterprise 2' => 'ENT2',
            'lab ent 1' => 'ENT1',
            'lab ent 2' => 'ENT2',
            'lab ent1' => 'ENT1',
            'lab ent2' => 'ENT2',
            'lab multimedia' => 'MMD',
            'lab mmd' => 'MMD',
            'lab networking' => 'Network',
            'lab network' => 'Network',
            'lab intelligence 1' => 'INT1',
            'lab intelligence 2' => 'INT2',
            'lab int 1' => 'INT1',
            'lab int 2' => 'INT2',
            'lab int1' => 'INT1',
            'lab int2' => 'INT2',
        ];

        $dayMapping = [
            'senin' => 1,
            'selasa' => 2,
            'rabu' => 3,
            'kamis' => 4,
            'jumat' => 5,
            'jum\'at' => 5,
            'sabtu' => 6,
        ];

        DB::transaction(function () use ($rows, $now, $dosenRoleId, $validProdiIds, $roomMapping, $dayMapping) {
            $headerMap = [];

            foreach ($rows as $index => $row) {
                $firstCell = strtolower(trim((string) ($row[0] ?? '')));
                $secondCell = strtolower(trim((string) ($row[1] ?? '')));

                if (str_contains($firstCell, 'semester') || str_contains($secondCell, 'kode')) {
                    $headerMap = [];
                    foreach ($row as $colIdx => $colName) {
                        $c = strtolower(trim((string) $colName));
                        $headerMap[$c] = $colIdx;
                    }
                    continue;
                }

                $getVal = function ($keys, $defaultIndex = null) use ($row, $headerMap) {
                    foreach ((array) $keys as $k) {
                        $lowerK = strtolower($k);
                        if (isset($headerMap[$lowerK]) && isset($row[$headerMap[$lowerK]])) {
                            $val = $row[$headerMap[$lowerK]];
                            return is_string($val) ? trim($val) : $val;
                        }
                    }
                    if ($defaultIndex !== null && isset($row[$defaultIndex])) {
                        $val = $row[$defaultIndex];
                        return is_string($val) ? trim($val) : $val;
                    }
                    return null;
                };

                $kodeMk = $getVal(['kode', 'kode mk', 'kode_mk'], 1);
                $namaMk = $getVal(['nama indonesia', 'mata kuliah', 'nama'], 2);
                $nik = $getVal(['nik', 'dosen_id'], 3);
                $dosenNama = $getVal(['dosen', 'nama dosen'], 4);
                $kodeKelas = $getVal(['kodekelas', 'kode kelas', 'kelas'], 5) ?: 'A';
                $hariText = $getVal(['hari text', 'hari'], 6);
                $jamAwal = $getVal(['jamawal', 'jam awal'], 7);
                $jamAkhir = $getVal(['jamakhir', 'jam akhir'], 8);
                $ruangText = $getVal(['ruang', 'ruangan'], 9);

                if (! $kodeMk || ! $ruangText || ! $hariText || ! $jamAwal || ! $jamAkhir) {
                    $this->skippedCount++;
                    continue;
                }

                $cleanRuang = strtolower(trim((string) $ruangText));
                if (! isset($roomMapping[$cleanRuang])) {
                    $this->skippedCount++;
                    continue;
                }
                $roomId = $roomMapping[$cleanRuang];

                $cleanHari = strtolower(trim((string) $hariText));
                $dayOfWeek = $dayMapping[$cleanHari] ?? null;
                if (! $dayOfWeek) {
                    $this->skippedCount++;
                    continue;
                }

                $formatTime = function ($timeStr) {
                    $timeStr = str_replace('.', ':', trim((string) $timeStr));
                    if (is_numeric($timeStr) && str_contains((string) $timeStr, '.')) {
                        $parts = explode('.', (string) $timeStr);
                        return sprintf('%02d:%02d:00', (int) $parts[0], (int) str_pad($parts[1] ?? '0', 2, '0'));
                    }
                    if (preg_match('/^(\d{1,2}):(\d{1,2})$/', $timeStr, $m)) {
                        return sprintf('%02d:%02d:00', (int) $m[1], (int) str_pad($m[2], 2, '0'));
                    }
                    if (preg_match('/^(\d{1,2}):(\d{1,2}):(\d{1,2})$/', $timeStr, $m)) {
                        return sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
                    }
                    return null;
                };

                $startTime = $formatTime($jamAwal);
                $endTime = $formatTime($jamAkhir);
                if (! $startTime || ! $endTime) {
                    $this->skippedCount++;
                    continue;
                }

                $type = (stripos((string) $namaMk, 'praktikum') !== false) ? 1 : 0;

                $userId = $nik ? trim((string) $nik) : null;
                if (! $userId || strtolower($userId) === 'team teaching' || strlen($userId) > 10) {
                    $userId = 'DOSEN_TEAM';
                }

                if ($userId !== 'DOSEN_TEAM') {
                    $prefixProdi = substr($userId, 0, 2);
                    $prodiId = in_array($prefixProdi, $validProdiIds, true) ? $prefixProdi : '00';
                    User::updateOrInsert(
                        ['id' => $userId],
                        [
                            'study_program_id' => $prodiId,
                            'name' => $dosenNama ?: "Dosen {$userId}",
                            'email' => "{$userId}@maranatha.ac.id",
                            'password' => Hash::make(Str::random(40)),
                            'is_active' => true,
                            'must_change_password' => true,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );

                    if ($dosenRoleId) {
                        UserRole::updateOrInsert([
                            'user_id' => $userId,
                            'role_id' => $dosenRoleId,
                            'period_id' => $this->period->id,
                        ], []);
                    }
                } else {
                    User::updateOrInsert(
                        ['id' => 'DOSEN_TEAM'],
                        [
                            'study_program_id' => '00',
                            'name' => $dosenNama ?: 'Team Teaching',
                            'email' => 'teamteaching@maranatha.ac.id',
                            'password' => Hash::make(Str::random(40)),
                            'is_active' => true,
                            'must_change_password' => true,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }

                $cleanKodeMk = trim((string) $kodeMk);
                $courseProdi = '72';
                if (str_starts_with($cleanKodeMk, 'SIB') || str_ends_with($cleanKodeMk, '73')) {
                    $courseProdi = '73';
                }

                Course::updateOrInsert(
                    ['id' => $cleanKodeMk],
                    [
                        'study_program_id' => $courseProdi,
                        'name' => $namaMk ?: $cleanKodeMk,
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                Schedule::updateOrInsert(
                    [
                        'period_id' => $this->period->id,
                        'course_id' => $cleanKodeMk,
                        'class_code' => substr((string) $kodeKelas, 0, 3) ?: 'A',
                        'type' => $type,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $startTime,
                        'room_id' => $roomId,
                    ],
                    [
                        'user_id' => $userId,
                        'end_time' => $endTime,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                $this->importedCount++;
            }

            $generator = new ScheduleSessionGenerator(new PeriodWeekCalendar());
            $generator->generateForPeriod($this->period);
        });
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }
}
