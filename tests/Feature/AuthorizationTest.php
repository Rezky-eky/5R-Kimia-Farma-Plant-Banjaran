<?php

namespace Tests\Feature;

use App\Models\GoAction;
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

    public function test_admin_dashboard_includes_the_logged_in_admins_personal_5r_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create(['role' => 'user']);

        GoAction::create([
            'user_id' => $admin->id,
            'npp_karyawan' => $admin->npp,
            'nama_karyawan' => $admin->name,
            'bagian' => 'Bagian Produksi I',
            'penjelasan_aksi' => 'Aksi pribadi admin',
            'list_barang_ringkas' => [['nama_barang' => 'Barang A']],
        ]);
        GoAction::create([
            'user_id' => $otherUser->id,
            'npp_karyawan' => $otherUser->npp,
            'nama_karyawan' => $otherUser->name,
            'bagian' => 'Bagian Produksi II',
            'penjelasan_aksi' => 'Aksi user lain',
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->where('personalStats.total_records', 1)
                ->where('personalStats.go_actions', 1)
                ->where('personalStats.ringkas_items', 1)
                ->where('personalStats.go_boosts', 0)
                ->where('personalStats.activity_breakdown.0.count', 1)
                ->has('personalStats.recent_records', 1)
                ->where('personalStats.recent_records.0.module', 'Go Action')
                ->where('personalStats.recent_records.0.description', 'Bagian Produksi I')
            );
    }

    public function test_five_r_ketua_can_manage_go_check(): void
    {
        $user = User::factory()->create(['role' => 'five_r_ketua']);

        $this->actingAs($user)->get('/kelola-go-check')->assertOk();
    }
}
