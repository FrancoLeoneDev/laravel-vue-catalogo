<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_sent_to_the_inventory_panel()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // routes/web.php intentionally redirects the starter kit's `dashboard`
        // route to the inventory panel; there is no standalone dashboard page.
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('admin.dashboard'));
    }
}
