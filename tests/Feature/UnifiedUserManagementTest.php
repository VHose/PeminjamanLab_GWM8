<?php

namespace Tests\Feature;

use App\Models\Period;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kalab;
    private Period $period;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAdmin = Role::create(['name' => 'Admin']);
        $roleKalab = Role::create(['name' => 'Kepala_Lab']);
        Role::create(['name' => 'Visitor']);

        $this->period = Period::create([
            'name' => 'Ganjil 2026/2027',
            'start_date' => '2026-09-14',
            'end_date' => '2027-01-29',
            'uts_start' => '2026-11-02',
            'uts_end' => '2026-11-14',
            'uas_start' => '2027-01-18',
            'uas_end' => '2027-01-29',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'id' => 'ADM001',
            'name' => 'Admin Test',
            'email' => 'adm@test.com',
            'password' => bcrypt('password123'),
        ]);
        UserRole::create(['user_id' => $this->admin->id, 'role_id' => $roleAdmin->id, 'period_id' => $this->period->id]);

        $this->kalab = User::create([
            'id' => 'KLB001',
            'name' => 'Kalab Test',
            'email' => 'kalab@test.com',
            'password' => bcrypt('password123'),
        ]);
        UserRole::create(['user_id' => $this->kalab->id, 'role_id' => $roleKalab->id, 'period_id' => $this->period->id]);
    }

    public function test_both_admin_and_kalab_can_access_unified_user_management(): void
    {
        $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($this->kalab)->get(route('admin.users.index'))->assertOk();
    }

    public function test_newly_registered_user_appears_immediately_in_unified_management(): void
    {
        // Register a new visitor
        $this->post('/register', [
            'name' => 'Mahasiswa Baru',
            'email' => 'maba@maranatha.ac.id',
            'phone' => '0811223344',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $newVisitor = User::where('email', 'maba@maranatha.ac.id')->first();
        $this->assertNotNull($newVisitor);

        // Check in admin.users.index as Admin
        $resp = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $resp->assertOk();
        $resp->assertSee('Mahasiswa Baru');
        $resp->assertSee($newVisitor->id);

        // Check in admin.users.index as Kalab
        $respKalab = $this->actingAs($this->kalab)->get(route('admin.users.index'));
        $respKalab->assertOk();
        $respKalab->assertSee('Mahasiswa Baru');
    }
}
