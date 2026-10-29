<?php

namespace Tests\Feature;

use Tests\TestCase;

class GuestPanelTest extends TestCase
{
    public function test_homepage_returns_200_without_authentication(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('/login');
    }

    public function test_login_route_does_not_exist(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(404);
    }

    public function test_app_name_is_integra_health(): void
    {
        $this->assertSame('Integra Health', config('app.name'));
    }

    public function test_panel_shows_brand_name_to_guests(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Integra Health');
    }
}
