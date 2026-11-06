<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageResponsivenessAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('pos'));
        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_access_all_application_pages(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $routes = [
            'dashboard',
            'pos',
            'online-orders',
            'transactions',
            'menu',
            'categories',
            'ingredients',
            'purchases',
            'stock-opname',
            'reports',
            'settings',
        ];

        foreach ($routes as $route) {
            $response = $this->get(route($route));
            $response->assertOk();
            $response->assertSee('POS Kuliner');
        }
    }

    public function test_cashier_can_access_operational_pages_and_is_blocked_from_owner_pages(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier);

        // Allowed operational pages
        $allowedRoutes = [
            'pos',
            'online-orders',
            'transactions',
            'ingredients',
        ];

        foreach ($allowedRoutes as $route) {
            $response = $this->get(route($route));
            $response->assertOk();
        }

        // Restricted owner-only pages
        $restrictedRoutes = [
            'dashboard',
            'menu',
            'categories',
            'purchases',
            'stock-opname',
            'reports',
            'settings',
        ];

        foreach ($restrictedRoutes as $route) {
            $response = $this->get(route($route));
            $response->assertForbidden();
        }
    }
}
