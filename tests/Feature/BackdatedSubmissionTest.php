<?php

namespace Tests\Feature;

use App\Models\GoAction;
use App\Models\GoBoost;
use App\Models\GoCare;
use App\Models\GoCheck;
use App\Models\GoOffer;
use App\Models\GoSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackdatedSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_go_action_is_saved_under_the_selected_date(): void
    {
        $user = User::factory()->create();
        $entryDate = now()->subMonth()->toDateString();

        $this->actingAs($user)
            ->post(route('go_action.store'), [
                'entry_date' => $entryDate,
                'nama_karyawan' => $user->name,
                'bagian' => 'Bagian Produksi',
                'penjelasan_aksi' => 'Aksi yang dilakukan',
                'latitude' => '-6.9',
                'longitude' => '107.5',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($entryDate, GoAction::query()->sole()->created_at->toDateString());
    }

    public function test_go_boost_is_saved_under_the_selected_date(): void
    {
        $user = User::factory()->create();
        $entryDate = now()->subMonth()->toDateString();

        $this->actingAs($user)
            ->post(route('go_boost.store'), [
                'entry_date' => $entryDate,
                'bagian' => 'Bagian Produksi',
                'area_temuan' => 'Area Produksi',
                'ruangan_temuan' => 'Ruang A',
                'penjelasan_temuan' => 'Temuan yang perlu diperbaiki',
                'pic_terkait' => 'PIC Produksi',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($entryDate, GoBoost::query()->sole()->created_at->toDateString());
    }

    public function test_go_care_is_saved_under_the_selected_date(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $entryDate = now()->subMonth()->toDateString();

        $this->actingAs($user)
            ->post(route('go_care.store'), [
                'entry_date' => $entryDate,
                'bagian' => 'Bagian Produksi',
                'bagian_temuan' => 'Bagian Produksi',
                'penjelasan_temuan' => 'Temuan sebelum perbaikan',
                'photo_before' => [UploadedFile::fake()->create('before.jpg', 10, 'image/jpeg')],
                'penjelasan_capa' => 'Tindakan perbaikan',
                'photo_after' => [UploadedFile::fake()->create('after.jpg', 10, 'image/jpeg')],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($entryDate, GoCare::query()->sole()->created_at->toDateString());
    }

    public function test_go_boost_repair_backdates_the_existing_record(): void
    {
        $finder = User::factory()->create();
        $solver = User::factory()->create();
        $goBoost = GoBoost::create([
            'user_id' => $finder->id,
            'mentioned_user_id' => $solver->id,
            'area_temuan' => 'Area Produksi',
            'ruangan_temuan' => 'Ruang A',
            'penjelasan_temuan' => 'Temuan yang perlu diperbaiki',
            'pic_terkait' => 'PIC Produksi',
        ]);
        $entryDate = now()->subMonth()->toDateString();

        $this->actingAs($solver)
            ->post(route('go_boost.submitPerbaikan', $goBoost->id), [
                'entry_date' => $entryDate,
                'keterangan_perbaikan' => 'Perbaikan selesai',
            ]);

        $goBoost->refresh();
        $this->assertSame($entryDate, $goBoost->created_at->toDateString());
        $this->assertSame($entryDate, $goBoost->tanggal_perbaikan->toDateString());
        $this->assertDatabaseCount('go_boosts', 1);
    }

    public function test_go_check_repair_backdates_the_existing_record(): void
    {
        $finder = User::factory()->create();
        $solver = User::factory()->create();
        $goCheck = GoCheck::create([
            'finder_user_id' => $finder->id,
            'solver_user_id' => $solver->id,
            'bagian' => 'Bagian Produksi',
            'area_temuan' => 'Area Produksi',
            'ruangan_temuan' => 'Ruang A',
            'penjelasan_temuan' => 'Temuan yang perlu diperbaiki',
        ]);
        $entryDate = now()->subMonth()->toDateString();

        $this->actingAs($solver)
            ->post(route('go_check.submitPerbaikan', $goCheck->id), [
                'entry_date' => $entryDate,
                'keterangan_perbaikan' => 'Perbaikan selesai',
            ]);

        $goCheck->refresh();
        $this->assertSame($entryDate, $goCheck->created_at->toDateString());
        $this->assertSame($entryDate, $goCheck->tanggal_perbaikan->toDateString());
        $this->assertDatabaseCount('go_checks', 1);
    }

    public function test_go_offer_request_is_saved_under_the_selected_date(): void
    {
        $owner = User::factory()->create(['bagian' => 'Bagian A']);
        $requester = User::factory()->create(['bagian' => 'Bagian B']);
        $goAction = GoAction::create([
            'user_id' => $owner->id,
            'npp_karyawan' => $owner->npp,
            'nama_karyawan' => $owner->name,
            'bagian' => 'Bagian A',
            'penjelasan_aksi' => 'Data DBR',
            'list_barang_ringkas' => [[
                'nama_barang' => 'Barang',
                'jumlah' => 1,
                'satuan' => 'Unit',
                'distribution_type' => 'offer',
            ]],
        ]);
        $entryDate = now()->subMonth()->toDateString();

        $this->actingAs($requester)
            ->post(route('go_offer.store'), [
                'entry_date' => $entryDate,
                'go_action_id' => $goAction->id,
                'dbr_index' => 0,
                'mode' => 'request',
            ])
            ->assertRedirect(route('go_action.dbr_index'));

        $this->assertSame($entryDate, GoOffer::query()->sole()->created_at->toDateString());
    }

    public function test_go_sale_request_is_saved_under_the_selected_date(): void
    {
        $owner = User::factory()->create(['bagian' => 'Bagian A']);
        $requester = User::factory()->create(['bagian' => 'Bagian B']);
        $goAction = GoAction::create([
            'user_id' => $owner->id,
            'npp_karyawan' => $owner->npp,
            'nama_karyawan' => $owner->name,
            'bagian' => 'Bagian A',
            'penjelasan_aksi' => 'Data DBR',
            'list_barang_ringkas' => [[
                'nama_barang' => 'Barang',
                'jumlah' => 1,
                'satuan' => 'Unit',
                'distribution_type' => 'sale',
            ]],
        ]);
        $entryDate = now()->subMonth()->toDateString();

        $this->actingAs($requester)
            ->post(route('go_sale.store'), [
                'entry_date' => $entryDate,
                'go_action_id' => $goAction->id,
                'dbr_index' => 0,
                'mode' => 'request',
                'agreed_price' => 1000,
            ])
            ->assertRedirect(route('go_sale.index'));

        $this->assertSame($entryDate, GoSale::query()->sole()->created_at->toDateString());
    }

    public function test_future_dates_are_rejected_for_new_go_action_entries(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('go_action.create'))
            ->post(route('go_action.store'), [
                'entry_date' => now()->addDay()->toDateString(),
                'nama_karyawan' => $user->name,
                'bagian' => 'Bagian Produksi',
                'penjelasan_aksi' => 'Aksi yang dilakukan',
                'latitude' => '-6.9',
                'longitude' => '107.5',
            ])
            ->assertSessionHasErrors('entry_date');

        $this->assertDatabaseCount('go_actions', 0);
    }
}
