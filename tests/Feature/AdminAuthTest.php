<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    public function test_admin_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_seeded_admin_can_access_admin_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@soniperfume.com'))->first();

        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }
}
