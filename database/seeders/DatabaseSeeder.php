<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect(['Visitor', 'Staf_Lab', 'Kepala_Prodi', 'Kepala_Lab'])
            ->mapWithKeys(fn (string $name) => [$name => Role::query()->firstOrCreate(['name' => $name])]);

        foreach ([
            ['code' => 'IF', 'name' => 'S1 Teknik Informatika', 'color_hex' => '#FFF3B0'],
            ['code' => 'SI', 'name' => 'S1 Sistem Informasi', 'color_hex' => '#FBEFD1'],
            ['code' => 'S2', 'name' => 'S2 Magister Ilmu Komputer', 'color_hex' => '#FFB366'],
        ] as $studyProgram) {
            StudyProgram::query()->updateOrCreate(['code' => $studyProgram['code']], $studyProgram);
        }

        foreach ([
            ['name' => 'Staf Lab', 'email' => 'staf.lab@example.com', 'role' => 'Staf_Lab'],
            ['name' => 'Kepala Prodi', 'email' => 'kaprodi@example.com', 'role' => 'Kepala_Prodi'],
            ['name' => 'Kepala Lab', 'email' => 'kalab@example.com', 'role' => 'Kepala_Lab'],
        ] as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'role_id' => $roles[$account['role']]->id, 'password' => Hash::make('password')],
            );
        }
    }
}
