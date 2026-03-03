<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\GoAction;
use App\Models\GoBoost;
use App\Models\GoCare;
use App\Models\GoOffer;
use App\Models\GoSale;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Dashboard Admin - Menampilkan statistik dan grafik tren 5R.
     */
    public function dashboard()
    {
        // Statistik total laporan
        $totalGoActions = GoAction::count();
        $totalGoBoosts = GoBoost::count();
        $totalGoCares = GoCare::count();
        $totalAudited = Audit::count();
        $totalPending = GoAction::whereDoesntHave('audit')->count();

        // Grafik tren 5R (6 bulan terakhir)
        $trendData = $this->getTrendData();

        // Statistik per departemen
        $departementStats = $this->getDepartementStats();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_go_actions' => $totalGoActions,
                'total_go_boosts' => $totalGoBoosts,
                'total_go_cares' => $totalGoCares,
                'total_audited' => $totalAudited,
                'total_pending' => $totalPending,
            ],
            'trendData' => $trendData,
            'departementStats' => $departementStats,
        ]);
    }

    /**
     * Index Audit - Laporan Data 5R Keseluruhan (Go Action, Go Boost, Go Care, Go Offer, Go Sale).
     */
    public function auditIndex(Request $request)
    {
        $jenis = $request->input('jenis', '');
        $status = $request->input('status', '');
        $departemen = $request->input('departemen', '');
        $search = $request->input('search', '');
        $perPage = 15;

        $departements = $this->getAuditIndexDepartements();

        if ($jenis === 'go_action') {
            $items = $this->buildGoActionRows($request);
        } elseif ($jenis === 'go_boost') {
            $items = $this->buildGoBoostRows($request);
        } elseif ($jenis === 'go_care') {
            $items = $this->buildGoCareRows($request);
        } elseif ($jenis === 'go_offer') {
            $items = $this->buildGoOfferRows($request);
        } elseif ($jenis === 'go_sale') {
            $items = $this->buildGoSaleRows($request);
        } else {
            // Semua: gabung semua jenis
            $items = $this->buildGoActionRows($request)
                ->concat($this->buildGoBoostRows($request))
                ->concat($this->buildGoCareRows($request))
                ->concat($this->buildGoOfferRows($request))
                ->concat($this->buildGoSaleRows($request))
                ->sortByDesc('created_at_raw')
                ->values();
        }

        $total = $items->count();
        $page = (int) $request->input('page', 1);
        $slice = $items->forPage($page, $perPage)->values();
        $laporan = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Admin/AuditIndex', [
            'laporan' => $laporan,
            'departements' => $departements,
            'filters' => [
                'search' => $search,
                'departemen' => $departemen,
                'status' => $status,
                'jenis' => $jenis,
            ],
        ]);
    }

    private function getAuditIndexDepartements(): \Illuminate\Support\Collection
    {
        $bagian = GoAction::distinct()->pluck('bagian')->filter();
        $bagianBoost = GoBoost::distinct()->pluck('bagian')->filter();

        $bagianCare = collect();
        if (Schema::hasColumn('go_cares', 'bagian')) {
            $bagianCare = GoCare::distinct()->pluck('bagian')->filter();
        } elseif (Schema::hasColumn('go_cares', 'bagian_temuan')) {
            $bagianCare = GoCare::distinct()->pluck('bagian_temuan')->filter();
        }

        return $bagian->merge($bagianBoost)->merge($bagianCare)->unique()->sort()->values();
    }

    private function buildGoActionRows(Request $request): \Illuminate\Support\Collection
    {
        $query = GoAction::with(['user', 'audit.auditor'])->latest();
        $statusFilter = $request->input('status');
        if ($statusFilter === 'audited') {
            $query->whereHas('audit', fn ($q) => $q->where('score', '>', 0));
        }
        if ($statusFilter === 'pending') {
            $query->whereDoesntHave('audit');
        }
        if ($statusFilter === 'rejected') {
            $query->whereHas('audit', fn ($q) => $q->where('score', 0));
        }
        if ($request->input('departemen')) {
            $query->where('bagian', $request->input('departemen'));
        }
        if ($request->input('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('nama_karyawan', 'like', "%{$s}%")
                    ->orWhere('npp_karyawan', 'like', "%{$s}%")
                    ->orWhere('bagian', 'like', "%{$s}%")
                    ->orWhere('penjelasan_aksi', 'like', "%{$s}%")
                    ->orWhere('nama_ruangan', 'like', "%{$s}%");
            });
        }
        $limit = $request->input('jenis') === '' ? 200 : 500;
        return $query->limit($limit)->get()->map(function ($goAction) {
            $fotoPath = $goAction->foto_kegiatan_path;
            $fotoUrl = null;
            if ($fotoPath) {
                $decoded = json_decode($fotoPath, true);
                $path = is_array($decoded) ? ($decoded[0] ?? null) : $fotoPath;
                if ($path) {
                    $fotoUrl = asset('storage/' . $path);
                }
            }
            return [
                'id' => $goAction->id,
                'type' => 'go_action',
                'nama_karyawan' => $goAction->nama_karyawan,
                'npp_karyawan' => $goAction->npp_karyawan,
                'bagian' => $goAction->bagian,
                'nama_ruangan' => $goAction->nama_ruangan,
                'penjelasan_aksi' => $goAction->penjelasan_aksi,
                'foto_url' => $fotoUrl,
                'jenis_aksi' => 'GO ACTION',
                'status' => $goAction->audit ? ($goAction->audit->score === 0 ? 'Rejected' : 'Audited') : 'Pending',
                'created_at' => $goAction->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $goAction->created_at,
                'can_approve_reject' => true,
                'detail_route' => 'admin.audit.detail',
                'detail_id' => $goAction->id,
            ];
        });
    }

    private function buildGoBoostRows(Request $request): \Illuminate\Support\Collection
    {
        $query = GoBoost::with(['user'])->latest();
        if ($request->input('departemen')) {
            $query->where('bagian', $request->input('departemen'));
        }
        if ($request->input('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('nama_karyawan', 'like', "%{$s}%")
                    ->orWhere('npp_karyawan', 'like', "%{$s}%")
                    ->orWhere('bagian', 'like', "%{$s}%")
                    ->orWhere('area_temuan', 'like', "%{$s}%")
                    ->orWhere('ruangan_temuan', 'like', "%{$s}%")
                    ->orWhere('penjelasan_temuan', 'like', "%{$s}%");
            });
        }
        $limit = $request->input('jenis') === 'go_boost' ? 500 : 200;
        return $query->limit($limit)->get()->map(function ($row) {
            $fotoUrl = null;
            if ($row->photo_temuan) {
                $decoded = json_decode($row->photo_temuan, true);
                $path = is_array($decoded) ? ($decoded[0] ?? null) : $row->photo_temuan;
                if ($path) {
                    $fotoUrl = asset('storage/' . $path);
                }
            }
            return [
                'id' => $row->id,
                'type' => 'go_boost',
                'nama_karyawan' => $row->nama_karyawan,
                'npp_karyawan' => $row->npp_karyawan,
                'bagian' => $row->bagian,
                'nama_ruangan' => $row->ruangan_temuan ?? $row->area_temuan ?? '-',
                'penjelasan_aksi' => $row->penjelasan_temuan,
                'foto_url' => $fotoUrl,
                'jenis_aksi' => 'GO BOOST',
                'status' => $row->status ?? 'OPEN',
                'created_at' => $row->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $row->created_at,
                'can_approve_reject' => false,
                'detail_route' => 'admin.go_boost.detail',
                'detail_id' => $row->id,
            ];
        });
    }

    private function buildGoCareRows(Request $request): \Illuminate\Support\Collection
    {
        $query = GoCare::with(['user'])->latest();
        if ($request->input('departemen')) {
            $dept = $request->input('departemen');
            if (Schema::hasColumn('go_cares', 'bagian')) {
                $query->where('bagian', $dept);
            } elseif (Schema::hasColumn('go_cares', 'bagian_temuan')) {
                $query->where('bagian_temuan', $dept);
            }
        }
        if ($request->input('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('nama_karyawan', 'like', "%{$s}%")
                    ->orWhere('npp_karyawan', 'like', "%{$s}%")
                    ->orWhere('bagian', 'like', "%{$s}%")
                    ->orWhere('bagian_temuan', 'like', "%{$s}%")
                    ->orWhere('area_temuan', 'like', "%{$s}%")
                    ->orWhere('penjelasan_temuan', 'like', "%{$s}%")
                    ->orWhere('penjelasan_capa', 'like', "%{$s}%");
            });
        }
        $limit = $request->input('jenis') === 'go_care' ? 500 : 200;
        return $query->limit($limit)->get()->map(function ($row) {
            $fotoUrl = null;
            if ($row->photo_before) {
                $decoded = json_decode($row->photo_before, true);
                $path = is_array($decoded) ? ($decoded[0] ?? null) : $row->photo_before;
                if ($path) {
                    $fotoUrl = asset('storage/' . $path);
                }
            }
            return [
                'id' => $row->id,
                'type' => 'go_care',
                'nama_karyawan' => $row->nama_karyawan,
                'npp_karyawan' => $row->npp_karyawan,
                'bagian' => $row->bagian,
                'nama_ruangan' => $row->area_temuan ?? $row->bagian_temuan ?? '-',
                'penjelasan_aksi' => $row->penjelasan_temuan ?? $row->penjelasan_capa,
                'foto_url' => $fotoUrl,
                'jenis_aksi' => 'GO CARE',
                'status' => '-',
                'created_at' => $row->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $row->created_at,
                'can_approve_reject' => false,
                'detail_route' => 'admin.go_care.detail',
                'detail_id' => $row->id,
            ];
        });
    }

    private function buildGoOfferRows(Request $request): \Illuminate\Support\Collection
    {
        $query = GoOffer::with(['goAction', 'offeredByUser'])->latest();
        if ($request->input('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('offered_by_bagian', 'like', "%{$s}%")
                    ->orWhere('target_bagian', 'like', "%{$s}%");
            });
        }
        $limit = $request->input('jenis') === 'go_offer' ? 500 : 200;
        return $query->limit($limit)->get()->map(function ($row) {
            $fotoUrl = null;
            if ($row->goAction && $row->goAction->foto_kegiatan_path) {
                $decoded = json_decode($row->goAction->foto_kegiatan_path, true);
                $path = is_array($decoded) ? ($decoded[0] ?? null) : $row->goAction->foto_kegiatan_path;
                if ($path) {
                    $fotoUrl = asset('storage/' . $path);
                }
            }
            $nama = $row->offeredByUser->name ?? '-';
            $npp = $row->offeredByUser->npp ?? '-';
            return [
                'id' => $row->id,
                'type' => 'go_offer',
                'nama_karyawan' => $nama,
                'npp_karyawan' => $npp,
                'bagian' => $row->offered_by_bagian ?? '-',
                'nama_ruangan' => $row->target_bagian ?? '-',
                'penjelasan_aksi' => 'Tawaran ke ' . ($row->target_bagian ?? '-'),
                'foto_url' => $fotoUrl,
                'jenis_aksi' => 'GO OFFER',
                'status' => $row->status ?? '-',
                'created_at' => $row->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $row->created_at,
                'can_approve_reject' => false,
                'detail_route' => 'go_offer.index',
                'detail_id' => null,
            ];
        });
    }

    private function buildGoSaleRows(Request $request): \Illuminate\Support\Collection
    {
        $query = GoSale::with(['goAction', 'sellerUser'])->latest();
        if ($request->input('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('seller_bagian', 'like', "%{$s}%")
                    ->orWhere('buyer_bagian', 'like', "%{$s}%");
            });
        }
        $limit = $request->input('jenis') === 'go_sale' ? 500 : 200;
        return $query->limit($limit)->get()->map(function ($row) {
            $fotoUrl = null;
            if ($row->goAction && $row->goAction->foto_kegiatan_path) {
                $decoded = json_decode($row->goAction->foto_kegiatan_path, true);
                $path = is_array($decoded) ? ($decoded[0] ?? null) : $row->goAction->foto_kegiatan_path;
                if ($path) {
                    $fotoUrl = asset('storage/' . $path);
                }
            }
            $nama = $row->sellerUser->name ?? '-';
            $npp = $row->sellerUser->npp ?? '-';
            return [
                'id' => $row->id,
                'type' => 'go_sale',
                'nama_karyawan' => $nama,
                'npp_karyawan' => $npp,
                'bagian' => $row->seller_bagian ?? '-',
                'nama_ruangan' => $row->buyer_bagian ?? '-',
                'penjelasan_aksi' => 'Penjualan ke ' . ($row->buyer_bagian ?? '-'),
                'foto_url' => $fotoUrl,
                'jenis_aksi' => 'GO SALE',
                'status' => $row->status ?? '-',
                'created_at' => $row->created_at->format('d/m/Y H:i'),
                'created_at_raw' => $row->created_at,
                'can_approve_reject' => false,
                'detail_route' => 'go_sale.index',
                'detail_id' => null,
            ];
        });
    }

    /**
     * Detail Audit - Menampilkan detail laporan untuk audit.
     */
    public function auditDetail($id)
    {
        $goAction = GoAction::with(['user', 'audit.auditor'])
            ->findOrFail($id);

        // Parse foto jika berupa JSON array
        $fotos = [];
        if ($goAction->foto_kegiatan_path) {
            $fotoData = json_decode($goAction->foto_kegiatan_path, true);
            if (is_array($fotoData)) {
                $fotos = array_map(function ($path) {
                    return asset('storage/' . $path);
                }, $fotoData);
            } else {
                $fotos = [asset('storage/' . $goAction->foto_kegiatan_path)];
            }
        }

        return Inertia::render('Admin/AuditDetail', [
            'goAction' => [
                'id' => $goAction->id,
                'nama_karyawan' => $goAction->nama_karyawan,
                'npp_karyawan' => $goAction->npp_karyawan,
                'bagian' => $goAction->bagian,
                'nama_ruangan' => $goAction->nama_ruangan,
                'penjelasan_aksi' => $goAction->penjelasan_aksi,
                'fotos' => $fotos,
                'list_barang_ringkas' => $goAction->list_barang_ringkas,
                'latitude' => $goAction->latitude,
                'longitude' => $goAction->longitude,
                'created_at' => $goAction->created_at->format('d/m/Y H:i'),
                'audit' => $goAction->audit ? [
                    'score' => $goAction->audit->score,
                    'notes' => $goAction->audit->notes,
                    'auditor_name' => $goAction->audit->auditor->name ?? 'N/A',
                    'created_at' => $goAction->audit->created_at->format('d/m/Y H:i'),
                ] : null,
            ],
        ]);
    }

    /**
     * Store Audit - Menyimpan hasil penilaian audit.
     */
    public function storeAudit(Request $request, $id)
    {
        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:10',
            'notes' => 'nullable|string|max:1000',
        ]);

        $goAction = GoAction::findOrFail($id);

        // Cek apakah sudah ada audit
        $audit = Audit::where('go_action_id', $id)->first();

        $userId = Auth::user()->id;

        if ($audit) {
            // Update audit yang sudah ada
            $audit->update([
                'score' => $validated['score'],
                'notes' => $validated['notes'] ?? null,
                'auditor_id' => $userId,
            ]);
        } else {
            // Buat audit baru (Go Action: hanya catat skor, tanpa poin)
            Audit::create([
                'go_action_id' => $id,
                'score' => $validated['score'],
                'notes' => $validated['notes'] ?? null,
                'auditor_id' => $userId,
            ]);
        }

        return redirect()
            ->route('admin.audit.index')
            ->with('success', 'Audit berhasil disimpan.');
    }

    /**
     * Approve laporan Go Action - Hanya menyetujui, tanpa memberi poin.
     * Poin untuk: Go Boost (saat solver selesai perbaikan, finder + solver masing-masing 10),
     * Go Care (10 poin saat submit laporan).
     */
    public function auditApprove($id)
    {
        $goAction = GoAction::findOrFail($id);

        $audit = Audit::where('go_action_id', $id)->first();

        if ($audit) {
            if ($audit->score === 0) {
                $audit->update([
                    'score' => 10,
                    'notes' => $audit->notes ?: 'Disetujui',
                    'auditor_id' => Auth::user()->id,
                ]);
            }
            return redirect()
                ->route('admin.audit.index')
                ->with('success', 'Laporan sudah diaudit sebelumnya.');
        }

        Audit::create([
            'go_action_id' => $id,
            'score' => 10,
            'notes' => 'Disetujui',
            'auditor_id' => Auth::user()->id,
        ]);

        return redirect()
            ->route('admin.audit.index')
            ->with('success', 'Laporan berhasil disetujui.');
    }

    /**
     * Reject laporan - Menolak laporan dengan menyimpan audit skor 0 (tanpa poin).
     */
    public function auditReject(Request $request, $id)
    {
        $goAction = GoAction::findOrFail($id);

        if (Audit::where('go_action_id', $id)->exists()) {
            return redirect()
                ->route('admin.audit.index')
                ->with('error', 'Laporan ini sudah diaudit.');
        }

        Audit::create([
            'go_action_id' => $id,
            'score' => 0,
            'notes' => $request->input('notes', 'Ditolak'),
            'auditor_id' => Auth::user()->id,
        ]);

        return redirect()
            ->route('admin.audit.index')
            ->with('success', 'Laporan berhasil ditolak.');
    }

    /**
     * Index Rewards - Menampilkan daftar rewards.
     */
    public function rewardIndex()
    {
        $rewards = Reward::latest()->paginate(15);

        return Inertia::render('Admin/RewardManager', [
            'rewards' => $rewards->through(function ($reward) {
                return [
                    'id' => $reward->id,
                    'title' => $reward->title,
                    'points_required' => $reward->points_required,
                    'stock' => $reward->stock,
                    'image_path' => $reward->image_path ? asset('storage/' . $reward->image_path) : null,
                    'is_active' => $reward->is_active,
                    'created_at' => $reward->created_at->format('d/m/Y H:i'),
                ];
            }),
        ]);
    }

    /**
     * Store Reward - Menyimpan reward baru.
     */
    public function rewardStore(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'points_required' => 'required|integer|min:1',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'is_active' => 'boolean',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('rewards', 'public');
        }

        Reward::create([
            'title' => $validated['title'],
            'points_required' => $validated['points_required'],
            'stock' => $validated['stock'],
            'image_path' => $imagePath,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.reward.index')
            ->with('success', 'Reward berhasil ditambahkan.');
    }

    /**
     * Update Reward - Update reward yang sudah ada.
     */
    public function rewardUpdate(Request $request, $id)
    {
        $reward = Reward::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'points_required' => 'required|integer|min:1',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'is_active' => 'boolean',
        ]);

        $imagePath = $reward->image_path;
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($reward->image_path) {
                Storage::disk('public')->delete($reward->image_path);
            }
            $imagePath = $request->file('image')->store('rewards', 'public');
        }

        $reward->update([
            'title' => $validated['title'],
            'points_required' => $validated['points_required'],
            'stock' => $validated['stock'],
            'image_path' => $imagePath,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.reward.index')
            ->with('success', 'Reward berhasil diperbarui.');
    }

    /**
     * Destroy Reward - Hapus reward.
     */
    public function rewardDestroy($id)
    {
        $reward = Reward::findOrFail($id);

        // Hapus gambar jika ada
        if ($reward->image_path) {
            Storage::disk('public')->delete($reward->image_path);
        }

        $reward->delete();

        return redirect()
            ->route('admin.reward.index')
            ->with('success', 'Reward berhasil dihapus.');
    }

    /**
     * Go Reward - Dashboard pemenang berdasarkan aktivitas (ganti Kelola Reward).
     */
    public function goReward()
    {
        // Pemenang Go Boost terbanyak (yang membuat temuan)
        $topGoBoostCreators = GoBoost::select('user_id', DB::raw('count(*) as total'))
            ->groupBy('user_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->with('user:id,name,npp')
            ->get()
            ->map(function ($row) {
                return [
                    'user_id' => $row->user_id,
                    'name' => $row->user?->name,
                    'npp' => $row->user?->npp,
                    'total' => $row->total,
                ];
            });

        // Pemenang Go Solver terbanyak (yang menyelesaikan perbaikan / mentioned_user yang submit perbaikan)
        $topGoSolvers = GoBoost::whereNotNull('mentioned_user_id')
            ->where('status', 'CLOSED')
            ->select('mentioned_user_id', DB::raw('count(*) as total'))
            ->groupBy('mentioned_user_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();
        $mentionedIds = $topGoSolvers->pluck('mentioned_user_id')->unique()->filter()->values();
        $usersMap = User::whereIn('id', $mentionedIds)->get()->keyBy('id');
        $topGoSolversList = $topGoSolvers->map(function ($row) use ($usersMap) {
            $u = $usersMap->get($row->mentioned_user_id);
            return [
                'user_id' => $row->mentioned_user_id,
                'name' => $u?->name,
                'npp' => $u?->npp,
                'total' => $row->total,
            ];
        });

        // Pemenang Go Care terbanyak
        $topGoCares = GoCare::select('user_id', DB::raw('count(*) as total'))
            ->groupBy('user_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->with('user:id,name,npp')
            ->get()
            ->map(function ($row) {
                return [
                    'user_id' => $row->user_id,
                    'name' => $row->user?->name,
                    'npp' => $row->user?->npp,
                    'total' => $row->total,
                ];
            });

        // Statistik bagian/departemen paling rajin implementasi 5R (gabungan Go Action + Go Boost + Go Care)
        $bagianGoAction = GoAction::select('bagian', DB::raw('count(*) as total'))->groupBy('bagian')->pluck('total', 'bagian');
        $bagianGoBoost = GoBoost::select('bagian', DB::raw('count(*) as total'))->groupBy('bagian')->pluck('total', 'bagian');
        // GoCare: pakai bagian_temuan (kolom yang pasti ada di tabel go_cares)
        $bagianGoCare = GoCare::select('bagian_temuan', DB::raw('count(*) as total'))
            ->groupBy('bagian_temuan')
            ->pluck('total', 'bagian_temuan');
        $allBagian = $bagianGoAction->keys()->merge($bagianGoBoost->keys())->merge($bagianGoCare->keys())->unique()->filter();
        $departementStats = $allBagian->map(function ($bagian) use ($bagianGoAction, $bagianGoBoost, $bagianGoCare) {
            $total = ($bagianGoAction[$bagian] ?? 0) + ($bagianGoBoost[$bagian] ?? 0) + ($bagianGoCare[$bagian] ?? 0);
            return ['bagian' => $bagian, 'total' => $total];
        })->sortByDesc('total')->take(10)->values();

        // Ranking poin (points_balance) untuk reward
        $topUsersByPoints = User::where('role', 'user')
            ->orderByDesc('points_balance')
            ->limit(15)
            ->get(['id', 'name', 'npp', 'points_balance', 'bagian'])
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'npp' => $user->npp,
                    'bagian' => $user->bagian,
                    'points_balance' => (int) $user->points_balance,
                ];
            });

        return Inertia::render('Admin/GoReward', [
            'topGoBoostCreators' => $topGoBoostCreators,
            'topGoSolvers' => $topGoSolversList,
            'topGoCares' => $topGoCares,
            'departementStats' => $departementStats,
            'topUsersByPoints' => $topUsersByPoints,
        ]);
    }

    /**
     * Get trend data untuk grafik 6 bulan terakhir.
     */
    private function getTrendData()
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'bulan' => $date->format('M'),
                'open' => GoAction::whereDoesntHave('audit')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'closed' => Audit::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        }
        return $months;
    }

    /**
     * Get statistik per departemen.
     */
    private function getDepartementStats()
    {
        return GoAction::select('bagian', DB::raw('count(*) as total'))
            ->groupBy('bagian')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'bagian' => $item->bagian,
                    'total' => $item->total,
                ];
            });
    }

    /**
     * Index Go Action - Menampilkan semua GO ACTION dengan detail lengkap.
     */
    public function goActionIndex(Request $request)
    {
        $query = GoAction::with(['user', 'audit.auditor'])
            ->latest();

        // Filter berdasarkan departemen
        if ($request->has('departemen') && $request->departemen) {
            $query->where('bagian', $request->departemen);
        }

        // Pencarian
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_karyawan', 'like', "%{$search}%")
                    ->orWhere('npp_karyawan', 'like', "%{$search}%")
                    ->orWhere('bagian', 'like', "%{$search}%")
                    ->orWhere('penjelasan_aksi', 'like', "%{$search}%")
                    ->orWhere('nama_ruangan', 'like', "%{$search}%");
            });
        }

        $goActions = $query->paginate(20)->through(function ($goAction) {
            // Parse foto jika berupa JSON array
            $fotos = [];
            if ($goAction->foto_kegiatan_path) {
                $fotoData = json_decode($goAction->foto_kegiatan_path, true);
                if (is_array($fotoData)) {
                    $fotos = array_map(function ($path) {
                        return asset('storage/' . $path);
                    }, $fotoData);
                } else {
                    $fotos = [asset('storage/' . $goAction->foto_kegiatan_path)];
                }
            }

            return [
                'id' => $goAction->id,
                'nama_karyawan' => $goAction->nama_karyawan,
                'npp_karyawan' => $goAction->npp_karyawan,
                'bagian' => $goAction->bagian,
                'nama_ruangan' => $goAction->nama_ruangan,
                'penjelasan_aksi' => $goAction->penjelasan_aksi,
                'fotos' => $fotos,
                'list_barang_ringkas' => $goAction->list_barang_ringkas,
                'latitude' => $goAction->latitude,
                'longitude' => $goAction->longitude,
                'created_at' => $goAction->created_at->format('d/m/Y H:i'),
                'user_name' => $goAction->user->name ?? 'N/A',
                'status' => $goAction->audit ? 'Audited' : 'Pending',
                'score' => $goAction->audit?->score,
                'auditor_name' => $goAction->audit?->auditor?->name,
            ];
        });

        // Ambil daftar departemen untuk filter
        $departements = GoAction::distinct()->pluck('bagian')->filter()->sort()->values();

        return Inertia::render('Admin/GoActionIndex', [
            'goActions' => $goActions,
            'departements' => $departements,
            'filters' => [
                'search' => $request->search ?? '',
                'departemen' => $request->departemen ?? '',
            ],
        ]);
    }

    /**
     * Index Go Boost - Menampilkan semua GO BOOST dengan detail lengkap.
     */
    public function goBoostIndex(Request $request)
    {
        $query = GoBoost::with(['user', 'mentionedUser'])
            ->latest();

        // Filter berdasarkan status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan status perbaikan
        if ($request->has('status_perbaikan') && $request->status_perbaikan) {
            $query->where('status_perbaikan', $request->status_perbaikan);
        }

        // Pencarian
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_karyawan', 'like', "%{$search}%")
                    ->orWhere('npp_karyawan', 'like', "%{$search}%")
                    ->orWhere('bagian', 'like', "%{$search}%")
                    ->orWhere('area_temuan', 'like', "%{$search}%")
                    ->orWhere('ruangan_temuan', 'like', "%{$search}%")
                    ->orWhere('penjelasan_temuan', 'like', "%{$search}%");
            });
        }

        $goBoosts = $query->paginate(20)->through(function ($goBoost) {
            // Parse foto temuan jika berupa JSON array
            $fotoTemuan = [];
            if ($goBoost->photo_temuan) {
                $fotoData = json_decode($goBoost->photo_temuan, true);
                if (is_array($fotoData)) {
                    $fotoTemuan = array_map(function ($path) {
                        return asset('storage/' . $path);
                    }, $fotoData);
                } else {
                    $fotoTemuan = [asset('storage/' . $goBoost->photo_temuan)];
                }
            }

            // Parse foto perbaikan jika ada
            $fotoPerbaikan = [];
            if ($goBoost->foto_perbaikan) {
                $fotoData = json_decode($goBoost->foto_perbaikan, true);
                if (is_array($fotoData)) {
                    $fotoPerbaikan = array_map(function ($path) {
                        return asset('storage/' . $path);
                    }, $fotoData);
                }
            }

            return [
                'id' => $goBoost->id,
                'nama_karyawan' => $goBoost->nama_karyawan,
                'npp_karyawan' => $goBoost->npp_karyawan,
                'bagian' => $goBoost->bagian,
                'area_temuan' => $goBoost->area_temuan,
                'ruangan_temuan' => $goBoost->ruangan_temuan,
                'penjelasan_temuan' => $goBoost->penjelasan_temuan,
                'pic_terkait' => $goBoost->pic_terkait,
                'foto_temuan' => $fotoTemuan,
                'status' => $goBoost->status ?? 'OPEN',
                'mentioned_user_id' => $goBoost->mentioned_user_id,
                'mentioned_user_name' => $goBoost->mentionedUser->name ?? null,
                'keterangan_perbaikan' => $goBoost->keterangan_perbaikan,
                'foto_perbaikan' => $fotoPerbaikan,
                'status_perbaikan' => $goBoost->status_perbaikan ?? 'pending',
                'tanggal_perbaikan' => $goBoost->tanggal_perbaikan 
                    ? (is_string($goBoost->tanggal_perbaikan) 
                        ? \Carbon\Carbon::parse($goBoost->tanggal_perbaikan)->format('d/m/Y H:i')
                        : $goBoost->tanggal_perbaikan->format('d/m/Y H:i'))
                    : null,
                'created_at' => $goBoost->created_at->format('d/m/Y H:i'),
                'user_name' => $goBoost->user->name ?? 'N/A',
            ];
        });

        return Inertia::render('Admin/GoBoostIndex', [
            'goBoosts' => $goBoosts,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
                'status_perbaikan' => $request->status_perbaikan ?? '',
            ],
        ]);
    }

    /**
     * Detail satu laporan Go Boost (untuk link dari Laporan 5R Keseluruhan).
     */
    public function goBoostDetail($id)
    {
        $goBoost = GoBoost::with(['user', 'mentionedUser'])->findOrFail($id);

        $fotoTemuan = [];
        if ($goBoost->photo_temuan) {
            $fotoData = json_decode($goBoost->photo_temuan, true);
            if (is_array($fotoData)) {
                $fotoTemuan = array_map(fn ($path) => asset('storage/' . $path), $fotoData);
            } else {
                $fotoTemuan = [asset('storage/' . $goBoost->photo_temuan)];
            }
        }
        $fotoPerbaikan = [];
        if ($goBoost->foto_perbaikan) {
            $fotoData = json_decode($goBoost->foto_perbaikan, true);
            if (is_array($fotoData)) {
                $fotoPerbaikan = array_map(fn ($path) => asset('storage/' . $path), $fotoData);
            }
        }

        $item = [
            'id' => $goBoost->id,
            'nama_karyawan' => $goBoost->nama_karyawan,
            'npp_karyawan' => $goBoost->npp_karyawan,
            'bagian' => $goBoost->bagian,
            'area_temuan' => $goBoost->area_temuan,
            'ruangan_temuan' => $goBoost->ruangan_temuan,
            'penjelasan_temuan' => $goBoost->penjelasan_temuan,
            'pic_terkait' => $goBoost->pic_terkait,
            'foto_temuan' => $fotoTemuan,
            'status' => $goBoost->status ?? 'OPEN',
            'mentioned_user_name' => $goBoost->mentionedUser->name ?? null,
            'keterangan_perbaikan' => $goBoost->keterangan_perbaikan,
            'foto_perbaikan' => $fotoPerbaikan,
            'status_perbaikan' => $goBoost->status_perbaikan ?? 'pending',
            'tanggal_perbaikan' => $goBoost->tanggal_perbaikan
                ? (is_string($goBoost->tanggal_perbaikan) ? \Carbon\Carbon::parse($goBoost->tanggal_perbaikan)->format('d/m/Y H:i') : $goBoost->tanggal_perbaikan->format('d/m/Y H:i'))
                : null,
            'created_at' => $goBoost->created_at->format('d/m/Y H:i'),
            'user_name' => $goBoost->user->name ?? 'N/A',
        ];

        return Inertia::render('Admin/GoBoostDetail', ['goBoost' => $item]);
    }

    /**
     * Index Go Care - Menampilkan semua GO CARE dengan detail lengkap.
     */
    public function goCareIndex(Request $request)
    {
        $query = GoCare::with(['user'])
            ->latest();

        // Pencarian
        if ($request->has('search') && $request->search) {
            $search = $request->search;
                $query->where(function ($q) use ($search) {
                $q->where('nama_karyawan', 'like', "%{$search}%")
                    ->orWhere('npp_karyawan', 'like', "%{$search}%")
                    ->orWhere('bagian', 'like', "%{$search}%")
                    ->orWhere('bagian_temuan', 'like', "%{$search}%")
                    ->orWhere('area_temuan', 'like', "%{$search}%")
                    ->orWhere('penjelasan_temuan', 'like', "%{$search}%")
                    ->orWhere('penjelasan_capa', 'like', "%{$search}%");
            });
        }

        $goCares = $query->paginate(20)->through(function ($goCare) {
            // Parse foto before jika berupa JSON array
            $fotoBefore = [];
            if ($goCare->photo_before) {
                $fotoData = json_decode($goCare->photo_before, true);
                if (is_array($fotoData)) {
                    $fotoBefore = array_map(function ($path) {
                        return asset('storage/' . $path);
                    }, $fotoData);
                } else {
                    $fotoBefore = [asset('storage/' . $goCare->photo_before)];
                }
            }

            // Parse foto after jika berupa JSON array
            $fotoAfter = [];
            if ($goCare->photo_after) {
                $fotoData = json_decode($goCare->photo_after, true);
                if (is_array($fotoData)) {
                    $fotoAfter = array_map(function ($path) {
                        return asset('storage/' . $path);
                    }, $fotoData);
                } else {
                    $fotoAfter = [asset('storage/' . $goCare->photo_after)];
                }
            }

            return [
                'id' => $goCare->id,
                'nama_karyawan' => $goCare->nama_karyawan,
                'npp_karyawan' => $goCare->npp_karyawan,
                'bagian' => $goCare->bagian,
                'bagian_temuan' => $goCare->bagian_temuan,
                'area_temuan' => $goCare->area_temuan,
                'penjelasan_temuan' => $goCare->penjelasan_temuan,
                'foto_before' => $fotoBefore,
                'penjelasan_capa' => $goCare->penjelasan_capa,
                'foto_after' => $fotoAfter,
                'created_at' => $goCare->created_at->format('d/m/Y H:i'),
                'user_name' => $goCare->user->name ?? 'N/A',
            ];
        });

        return Inertia::render('Admin/GoCareIndex', [
            'goCares' => $goCares,
            'filters' => [
                'search' => $request->search ?? '',
            ],
        ]);
    }

    /**
     * Detail satu laporan Go Care (untuk link dari Laporan 5R Keseluruhan).
     */
    public function goCareDetail($id)
    {
        $goCare = GoCare::with(['user'])->findOrFail($id);

        $fotoBefore = [];
        if ($goCare->photo_before) {
            $fotoData = json_decode($goCare->photo_before, true);
            if (is_array($fotoData)) {
                $fotoBefore = array_map(fn ($path) => asset('storage/' . $path), $fotoData);
            } else {
                $fotoBefore = [asset('storage/' . $goCare->photo_before)];
            }
        }
        $fotoAfter = [];
        if ($goCare->photo_after) {
            $fotoData = json_decode($goCare->photo_after, true);
            if (is_array($fotoData)) {
                $fotoAfter = array_map(fn ($path) => asset('storage/' . $path), $fotoData);
            } else {
                $fotoAfter = [asset('storage/' . $goCare->photo_after)];
            }
        }

        $item = [
            'id' => $goCare->id,
            'nama_karyawan' => $goCare->nama_karyawan,
            'npp_karyawan' => $goCare->npp_karyawan,
            'bagian' => $goCare->bagian ?? $goCare->bagian_temuan,
            'bagian_temuan' => $goCare->bagian_temuan,
            'area_temuan' => $goCare->area_temuan,
            'penjelasan_temuan' => $goCare->penjelasan_temuan,
            'foto_before' => $fotoBefore,
            'penjelasan_capa' => $goCare->penjelasan_capa,
            'foto_after' => $fotoAfter,
            'created_at' => $goCare->created_at->format('d/m/Y H:i'),
            'user_name' => $goCare->user->name ?? 'N/A',
        ];

        return Inertia::render('Admin/GoCareDetail', ['goCare' => $item]);
    }
}
