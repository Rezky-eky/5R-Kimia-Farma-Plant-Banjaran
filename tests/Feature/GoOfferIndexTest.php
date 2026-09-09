<?php

namespace Tests\Feature;

use App\Models\GoAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoOfferIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_by_search_and_keeps_query_in_pagination_links(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        for ($i = 0; $i < 16; $i++) {
            GoAction::create([
                'user_id' => $user->id,
                'npp_karyawan' => '123' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'nama_karyawan' => $i === 0 ? 'Rama' : 'Budi',
                'bagian' => $i === 0 ? 'IT' : 'Finance',
                'penjelasan_aksi' => "Test item {$i}",
                'list_barang_ringkas' => [[
                    'nama_barang' => $i === 0 ? 'Laptop Rama' : 'Monitor',
                    'jumlah' => 1,
                    'satuan' => 'unit',
                    'distribution_type' => 'offer',
                ]],
            ]);
        }

        $response = $this->actingAs($user)->get('/go-offer?search=Rama&page=1');

        $response->assertOk();

        $props = $response->viewData('page')['props'];

        $this->assertSame(1, $props['items']['total']);
        $this->assertSame(1, $props['items']['current_page']);
        $this->assertCount(1, $props['items']['data']);
        $this->assertSame('Laptop Rama', $props['items']['data'][0]['dbr_snapshot']['nama_barang']);
        $this->assertStringContainsString('search=Rama', $props['items']['first_page_url']);
    }
}
