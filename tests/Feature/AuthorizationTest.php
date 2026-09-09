<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_authenticated_routes(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_view_admin_data_or_manage_go_check(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/kelola-go-check')->assertForbidden();
    }

    public function test_five_r_team_can_view_admin_data_but_cannot_manage_go_check(): void
    {
        $user = User::factory()->create(['role' => 'five_r_team']);

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
        $this->actingAs($user)->get('/kelola-go-check')->assertForbidden();
    }

    public function test_five_r_ketua_can_manage_go_check(): void
    {
        $user = User::factory()->create(['role' => 'five_r_ketua']);

        $this->actingAs($user)->get('/kelola-go-check')->assertOk();
    }
}
