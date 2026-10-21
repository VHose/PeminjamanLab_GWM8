<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;
    public function test_welcome_page_uses_the_bootstrap_layout(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Peminjaman Ruangan Lab');
        $response->assertSee('bootstrap@5.3.3', false);
    }

    public function test_welcome_page_renders_interactive_floor_plan_for_all_labs(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('denah-interactive-wrapper');

        $labs = [
            'PROG1', 'PROG2', 'Network', 'INT1', 'INT2',
            'ADV1', 'ADV2', 'ADV3', 'ADV4', 'DB', 'MMD', 'ENT1', 'ENT2'
        ];

        foreach ($labs as $labId) {
            $response->assertSee('data-room-id="' . $labId . '"', false);
        }
    }

    public function test_booking_create_preselects_room_from_query_parameter(): void
    {
        \App\Models\Role::create(['name' => 'Visitor']);
        $period = \App\Models\Period::create([
            'name' => '2026/2027 Ganjil',
            'start_date' => '2026-09-14',
            'end_date' => '2027-01-29',
            'is_active' => true,
        ]);
        \App\Models\Room::create(['id' => 'PROG1', 'name' => 'Lab PROG1', 'capacity' => 40, 'is_active' => true]);

        $user = \App\Models\User::create([
            'id' => 'USER01',
            'name' => 'User Test',
            'email' => 'user@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get(route('bookings.create', ['room_id' => 'PROG1']));

        $response->assertOk();
        $response->assertSee('selected>PROG1', false);
    }
}
