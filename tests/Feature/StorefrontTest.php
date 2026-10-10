<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    public function test_home_page_is_accessible_and_renders_inertia(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Home'));
    }

    public function test_customer_auth_routes_do_not_exist(): void
    {
        $this->get('/login')->assertStatus(404);
        $this->get('/register')->assertStatus(404);
        $this->get('/forgot-password')->assertStatus(404);
        $this->get('/reset-password')->assertStatus(404);
        $this->get('/dashboard')->assertStatus(404);
        $this->get('/profile')->assertStatus(404);
    }
}
