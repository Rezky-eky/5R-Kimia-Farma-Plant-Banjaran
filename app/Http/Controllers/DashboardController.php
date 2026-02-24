<?php

namespace App\Http\Controllers;

use App\Models\GoAction;
use App\Models\GoBoost;
use App\Models\GoCare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        if ($user && $user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Statistik total
        $totalGoActions = GoAction::count();
        $totalGoBoosts = GoBoost::count();
        $totalGoCares = GoCare::count();

        // Grafik tren 5R (6 bulan terakhir)
        // Open = Go Action yang belum diaudit, Closed = Go Boost yang sudah ditutup
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
            'total_go_actions' => $totalGoActions,
            'total_go_boosts' => $totalGoBoosts,
            'total_go_cares' => $totalGoCares,
            'laporan_bulanan' => $laporanBulanan,
        ]);
    }
}
