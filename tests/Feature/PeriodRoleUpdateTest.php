<?php

namespace Tests\Feature;

use App\Models\Period;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PeriodRoleUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_kalab_can_add_multiple_roles_to_user_in_period(): void
    {
        $period = Period::create([
            'name' => '2026/2027 Ganjil',
            'start_date' => '2026-09-14',
            'end_date' => '2027-01-29',
            'uts_start' => '2026-11-02',
            'uts_end' => '2026-11-13',
            'uas_start' => '2027-01-18',
            'uas_end' => '2027-01-29',
            'is_active' => true,
        ]);

        $kalabRole = Role::create(['name' => 'Kepala_Lab']);
        $dosenRole = Role::create(['name' => 'Dosen']);
        $kaprodiRole = Role::create(['name' => 'Kepala_Prodi']);

        $kalab = User::create([
            'id' => 'KLB001',
            'name' => 'Kalab Test',
            'email' => 'kalab@test.com',
            'password' => Hash::make('password'),
        ]);
        UserRole::create(['user_id' => $kalab->id, 'role_id' => $kalabRole->id, 'period_id' => $period->id]);

        $dosen = User::create([
            'id' => 'DSN001',
            'name' => 'Robby Tan',
            'email' => 'robby@test.com',
            'password' => Hash::make('password'),
        ]);
        UserRole::create(['user_id' => $dosen->id, 'role_id' => $dosenRole->id, 'period_id' => $period->id]);

        $this->assertTrue($dosen->hasRole('Dosen'));
        $this->assertFalse($dosen->hasRole('Kepala_Prodi'));

        $response = $this->actingAs($kalab)
            ->put(route('admin.period-roles.update-user', [$period->id, $dosen->id]), [
                'role_ids' => [$dosenRole->id, $kaprodiRole->id],
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_role', [
            'user_id' => 'DSN001',
            'role_id' => $dosenRole->id,
            'period_id' => $period->id,
        ]);

        $this->assertDatabaseHas('user_role', [
            'user_id' => 'DSN001',
            'role_id' => $kaprodiRole->id,
            'period_id' => $period->id,
        ]);

        $dosen->refresh();
        $this->assertTrue($dosen->hasRole('Dosen'));
        $this->assertTrue($dosen->hasRole('Kepala_Prodi'));
    }
}
