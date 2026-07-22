<?php

namespace App\Http\Controllers;

use App\Models\GoAction;
use App\Models\GoBoost;
use App\Models\GoCare;
use App\Models\GoCheck;
use App\Models\GoOffer;
use App\Models\GoSale;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard user dengan data real dari database.
     */
    public function index()
    {
        // Jika user adalah admin, arahkan langsung ke Admin Dashboard
        $user = auth()->user();
        if ($user && $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user && ($user->isFiveRKetua() || $user->isFiveRSekretaris())) {
            return redirect()->route('go_check.management.dashboard');
        }

        // Statistik total semua data 5R
        $totalGoActions = GoAction::count();
        $totalGoBoosts = GoBoost::count();
        $totalGoCares = GoCare::count();
        $totalGoOffers = GoOffer::count();
        $totalGoSales = GoSale::count();
        $totalGoChecks = GoCheck::count();

        $totalRecordsAll = $totalGoActions + $totalGoBoosts + $totalGoCares + $totalGoOffers + $totalGoSales + $totalGoChecks;

        // Statistik personal user
        $userGoActions = GoAction::where('user_id', $user->id)->count();
        $userRingkasItems = GoAction::where('user_id', $user->id)
            ->whereNotNull('list_barang_ringkas')
            ->get()
            ->sum(fn ($goAction) => is_array($goAction->list_barang_ringkas) ? count($goAction->list_barang_ringkas) : 0);
        $userGoBoosts = GoBoost::where('user_id', $user->id)->count();
        $userGoCares = GoCare::where('user_id', $user->id)->count();
        $userGoOffers = GoOffer::where('requested_by_user_id', $user->id)->count();
        $userGoSales = GoSale::where(function ($query) use ($user) {
            $query->where('seller_user_id', $user->id)
                ->orWhere('buyer_user_id', $user->id);
        })->count();
        $userGoChecks = GoCheck::where(function ($query) use ($user) {
            $query->where('finder_user_id', $user->id)
                ->orWhere('solver_user_id', $user->id);
        })->count();

        $userTotalRecords = $userGoActions + $userGoBoosts + $userGoCares + $userGoOffers + $userGoSales + $userGoChecks;

        $recentItems = collect()
            ->concat(GoAction::where('user_id', $user->id)->latest()->get()->map(fn ($item) => [
                'id' => 'go-action-'.$item->id,
                'module' => 'Go Action',
                'description' => $item->bagian ?: 'Aksi Go Action',
                'created_at' => $item->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $item->created_at,
                'details' => [
                    ['label' => 'Ruangan', 'value' => $item->nama_ruangan ?: '-'],
                    ['label' => 'Penjelasan', 'value' => Str::limit($item->penjelasan_aksi ?? '-', 100)],
                    ['label' => 'Jumlah Barang Ringkas', 'value' => is_array($item->list_barang_ringkas) ? count($item->list_barang_ringkas) : 0],
                ],
                'photos' => collect(json_decode($item->foto_kegiatan_path, true) ?? [])->filter()->map(fn ($path) => asset('storage/' . ltrim($path, '/')))->values()->all(),
            ]))
            ->concat(GoBoost::where('user_id', $user->id)->latest()->get()->map(fn ($item) => [
                'id' => 'go-boost-'.$item->id,
                'module' => 'Go Boost',
                'description' => $item->area_temuan ?: 'Go Boost',
                'created_at' => $item->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $item->created_at,
                'details' => [
                    ['label' => 'Bagian', 'value' => $item->bagian ?: '-'],
                    ['label' => 'Ruangan Temuan', 'value' => $item->ruangan_temuan ?: '-'],
                    ['label' => 'Status', 'value' => $item->status ?? 'OPEN'],
                    ['label' => 'PIC', 'value' => $item->pic_terkait ?: '-'],
                ],
                'photos' => collect(json_decode($item->photo_temuan, true) ?? [])->filter()->map(fn ($path) => asset('storage/' . ltrim($path, '/')))->values()->all(),
            ]))
            ->concat(GoCare::where('user_id', $user->id)->latest()->get()->map(fn ($item) => [
                'id' => 'go-care-'.$item->id,
                'module' => 'Go Care',
                'description' => $item->bagian_temuan ?: 'Go Care',
                'created_at' => $item->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $item->created_at,
                'details' => [
                    ['label' => 'Bagian Temuan', 'value' => $item->bagian_temuan ?: '-'],
                    ['label' => 'Penjelasan', 'value' => Str::limit($item->penjelasan_temuan ?? '-', 100)],
                    ['label' => 'Status Approval', 'value' => $item->approval_status ?? '-'],
                ],
                'photos' => collect(json_decode($item->photo_before, true) ?? [])
                    ->merge(collect(json_decode($item->photo_after, true) ?? []))
                    ->filter()
                    ->map(fn ($path) => asset('storage/' . ltrim($path, '/')))
                    ->values()
                    ->all(),
            ]))
            ->concat(GoOffer::where('requested_by_user_id', $user->id)->latest()->get()->map(fn ($item) => [
                'id' => 'go-offer-'.$item->id,
                'module' => 'Go Offer',
                'description' => 'Ke ' . ($item->target_bagian ?? 'bagian lain'),
                'created_at' => $item->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $item->created_at,
                'details' => [
                    ['label' => 'Target Bagian', 'value' => $item->target_bagian ?: '-'],
                    ['label' => 'Status', 'value' => $item->status ?? '-'],
                    ['label' => 'Dari DBR Index', 'value' => $item->dbr_index ?? '-'],
                ],
            ]))
            ->concat(GoSale::where(function ($query) use ($user) {
                $query->where('seller_user_id', $user->id)
                    ->orWhere('buyer_user_id', $user->id);
            })->latest()->get()->map(fn ($item) => [
                'id' => 'go-sale-'.$item->id,
                'module' => 'Go Sale',
                'description' => 'Status: ' . ($item->status ?? 'Pending'),
                'created_at' => $item->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $item->created_at,
                'details' => [
                    ['label' => 'Seller Bagian', 'value' => $item->seller_bagian ?: '-'],
                    ['label' => 'Buyer Bagian', 'value' => $item->buyer_bagian ?: '-'],
                    ['label' => 'Harga Sepakat', 'value' => $item->agreed_price !== null ? 'Rp ' . number_format($item->agreed_price, 2, ',', '.') : '-'],
                ],
            ]))
            ->concat(GoCheck::where(function ($query) use ($user) {
                $query->where('finder_user_id', $user->id)
                    ->orWhere('solver_user_id', $user->id);
            })->latest()->get()->map(fn ($item) => [
                'id' => 'go-check-'.$item->id,
                'module' => 'Go Check',
                'description' => $item->bagian ?: 'Go Check',
                'created_at' => $item->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $item->created_at,
                'details' => [
                    ['label' => 'Bagian', 'value' => $item->bagian ?: '-'],
                    ['label' => 'Status Perbaikan', 'value' => $item->status_perbaikan ?: '-'],
                    ['label' => 'Status', 'value' => $item->status ?: '-'],
                ],
                'photos' => collect(json_decode($item->photo_temuan, true) ?? [])->filter()->map(fn ($path) => asset('storage/' . ltrim($path, '/')))->values()->all(),
            ]))
            ->sortByDesc('created_at_raw')
            ->values();

        $userActivityBreakdown = [
            ['label' => 'Go Action', 'count' => $userGoActions],
            ['label' => 'Barang Ringkas', 'count' => $userRingkasItems],
            ['label' => 'Go Boost', 'count' => $userGoBoosts],
            ['label' => 'Go Care', 'count' => $userGoCares],
            ['label' => 'Go Offer', 'count' => $userGoOffers],
            ['label' => 'Go Sale', 'count' => $userGoSales],
            ['label' => 'Go Check', 'count' => $userGoChecks],
        ];

        // Grafik tren 5R (6 bulan terakhir)
        $laporanBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $laporanBulanan[] = [
                'bulan' => $date->format('M'),
                'open' => GoAction::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'closed' => GoBoost::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('status', 'CLOSED')
                    ->count() + GoCare::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        }

        return Inertia::render('Dashboard', [
            'total_records_all' => $totalRecordsAll,
            'user_total_records' => $userTotalRecords,
            'total_go_actions' => $totalGoActions,
            'total_go_boosts' => $totalGoBoosts,
            'total_go_cares' => $totalGoCares,
            'user_go_actions' => $userGoActions,
            'user_ringkas_items' => $userRingkasItems,
            'user_go_boosts' => $userGoBoosts,
            'user_go_cares' => $userGoCares,
            'user_go_offers' => $userGoOffers,
            'user_go_sales' => $userGoSales,
            'user_go_checks' => $userGoChecks,
            'user_activity_breakdown' => $userActivityBreakdown,
            'recent_user_records' => $recentItems,
            'show_go_check' => $userGoChecks > 0 || $user->isFiveRTeam(),
            'laporan_bulanan' => $laporanBulanan,
        ]);
    }
}
