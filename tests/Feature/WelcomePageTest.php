<?php

namespace Tests\Feature;

use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    public function test_welcome_page_uses_the_bootstrap_layout(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Peminjaman Ruangan Lab');
        $response->assertSee('bootstrap@5.3.3', false);
    }
}
