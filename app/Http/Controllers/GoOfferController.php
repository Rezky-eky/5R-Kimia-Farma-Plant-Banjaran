<?php

namespace App\Http\Controllers;

use App\Models\GoAction;
use App\Models\GoOffer;
use App\Models\GoSale;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class GoOfferController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        // Item DBR disimpan dalam JSON `go_actions.list_barang_ringkas`.
        // Item DBR dengan distribution_type offer atau sale (satu alur mutasi lewat Go Offer).
        $goActions = GoAction::with('user')
            ->whereNotNull('list_barang_ringkas')
            ->latest()
            ->limit(500)
            ->get();

        $goActionIds = $goActions->pluck('id')->values();

        $offers = GoOffer::with(['requestedByUser'])
            ->whereIn('go_action_id', $goActionIds)
            ->whereIn('status', ['pending', 'allocated', 'accepted', 'rejected'])
            ->get(['go_action_id', 'dbr_index', 'status', 'id', 'requested_by_user_id', 'accepted_at', 'created_at']);

        // Mapping ringkas_status per item.
        $offerBestByKey = [];
        foreach ($offers as $offer) {
            $key = $offer->go_action_id . '_' . $offer->dbr_index;

            $tracking = 'available';
            $priority = 0;
            if (in_array($offer->status, ['allocated', 'accepted'], true)) {
                $tracking = 'allocated';
                $priority = 2;
            } elseif ($offer->status === 'pending') {
                $tracking = 'requested';
                $priority = 1;
            }

            if (!isset($offerBestByKey[$key]) || $priority > $offerBestByKey[$key]['priority']) {
                $offerBestByKey[$key] = [
                    'tracking' => $tracking,
                    'priority' => $priority,
                    'offer_id' => $offer->id,
                    'requested_by_user_id' => $offer->requested_by_user_id,
                    'requested_by_name' => $offer->requestedByUser?->name,
                    'accepted_at' => $offer->accepted_at?->format('d/m/Y H:i'),
                    'created_at' => $offer->created_at?->format('d/m/Y H:i'),
                    'raw_status' => $offer->status,
                ];
            }
        }

        $rows = [];
        foreach ($goActions as $goAction) {
            $list = $goAction->list_barang_ringkas ?? [];
            if (!is_array($list)) {
                continue;
            }

            foreach ($list as $index => $barang) {
                $distributionType = $barang['distribution_type'] ?? null;
                if ($distributionType === null || $distributionType === '') {
                    $distributionType = 'offer';
                }
                // Satu fitur mutasi: item offer & sale (+ DBR tanpa jenis = diperlakukan seperti offer).
                if (!in_array($distributionType, ['offer', 'sale'], true)) {
                    continue;
                }

                $key = $goAction->id . '_' . $index;
                $best = $offerBestByKey[$key] ?? null;
                $trackingStatus = $best['tracking'] ?? 'available';

                $rows[] = [
                    'go_action_id' => $goAction->id,
                    'dbr_index' => (int) $index,
                    'created_at_raw' => $goAction->created_at->timestamp,
                    'created_at' => $goAction->created_at->format('d/m/Y H:i'),
                    'creator_user_id' => $goAction->user_id,
                    'creator_name' => $goAction->user?->name ?? '-',
                    'creator_bagian' => $goAction->bagian ?? '-',

                    'dbr_snapshot' => [
                        'nama_barang' => $barang['nama_barang'] ?? '-',
                        'jumlah' => $barang['jumlah'] ?? 0,
                        'satuan' => $barang['satuan'] ?? '-',
                        'no_aktiva_sap' => $barang['no_aktiva_sap'] ?? '-',
                        'status_tps' => $barang['status_tps'] ?? '-',
                        'tindakan_barang' => $barang['tindakan_barang'] ?? '-',
                        'kondisi_barang' => $barang['kondisi_barang'] ?? '-',
                    ],

                    'distribution_type' => $distributionType,
                    'ringkas_status' => $trackingStatus,

                    // Hanya diperlukan untuk action approval saat status requested.
                    'active_offer_id' => ($trackingStatus === 'requested') ? ($best['offer_id'] ?? null) : null,
                    'requested_by_user_id' => $best['requested_by_user_id'] ?? null,
                    'requested_by_name' => $best['requested_by_name'] ?? null,
                    'requested_created_at' => $best['created_at'] ?? null,
                    'allocated_accepted_at' => $best['accepted_at'] ?? null,
                ];
            }
        }

        $sortedRows = collect($rows)->sortByDesc('created_at_raw')->values();

        $perPage = 15;
        $page = (int) $request->input('page', 1);
        $slice = $sortedRows->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $sortedRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('GoOffer/Index', [
            'items' => $paginator,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'go_action_id' => 'required|exists:go_actions,id',
            'dbr_index' => 'required|integer|min:0',
        ]);

        $goAction = GoAction::findOrFail($validated['go_action_id']);
        $dbrIndex = (int) $validated['dbr_index'];

        $list = $goAction->list_barang_ringkas ?? [];
        $item = is_array($list) ? ($list[$dbrIndex] ?? null) : null;

        if (!$item) {
            return redirect()->route('go_action.dbr_index')->with('error', 'Item DBR tidak ditemukan.');
        }

        $distributionType = $item['distribution_type'] ?? null;
        if ($distributionType === null || $distributionType === '') {
            $distributionType = 'offer';
        }
        if (!in_array($distributionType, ['offer', 'sale'], true)) {
            return redirect()->route('go_action.dbr_index')->with('error', 'Item DBR tidak memiliki jenis distribusi yang valid (Offer/Sale).');
        }

        // Creator tidak boleh mengajukan mutasi ke bagian lain untuk barang yang diinput sendiri.
        if ((int) $goAction->user_id === (int) $user->id) {
            return redirect()->route('go_action.dbr_index')->with('error', 'Anda adalah pemilik entri DBR ini. Hanya bagian lain yang dapat mengajukan permintaan mutasi.');
        }

        $salePending = GoSale::where('go_action_id', $goAction->id)
            ->where('dbr_index', $dbrIndex)
            ->where('status', 'pending')
            ->exists();
        if ($salePending) {
            return redirect()->route('go_action.dbr_index')->with('error', 'Masih ada request Go Sale (legacy) yang belum diproses untuk item ini.');
        }

        return Inertia::render('GoOffer/Create', [
            'goAction' => [
                'id' => $goAction->id,
                'bagian' => $goAction->bagian,
                'list_barang_ringkas' => $goAction->list_barang_ringkas,
            ],
            'dbrIndex' => $dbrIndex,
            'dbrItem' => $item,
            'mode' => 'request',
            'userOptions' => [],
            'targetOptions' => [],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'go_action_id' => 'required|exists:go_actions,id',
            'dbr_index' => 'required|integer|min:0',
            'mode' => 'required|in:request',
        ]);

        $goAction = GoAction::findOrFail($validated['go_action_id']);
        $dbrIndex = (int) $validated['dbr_index'];
        $list = $goAction->list_barang_ringkas ?? [];
        $item = $this->resolveDbrItem(is_array($list) ? $list : [], $dbrIndex);

        if (!$item) {
            return back()->withErrors(['dbr_index' => 'Item DBR tidak ditemukan.'])->withInput();
        }

        $distributionType = $item['distribution_type'] ?? null;
        if ($distributionType === null || $distributionType === '') {
            $distributionType = 'offer';
        }
        if (!in_array($distributionType, ['offer', 'sale'], true)) {
            return back()->with('error', 'Item DBR tidak memiliki jenis distribusi yang valid (Offer/Sale).');
        }

        if ((int) $goAction->user_id === (int) $user->id) {
            return back()->with('error', 'Anda adalah pemilik entri DBR ini. Anda tidak dapat mengajukan permintaan mutasi.');
        }

        $salePending = GoSale::where('go_action_id', $goAction->id)
            ->where('dbr_index', $dbrIndex)
            ->where('status', 'pending')
            ->exists();
        if ($salePending) {
            return back()->with('error', 'Masih ada request Go Sale (legacy) yang belum diproses untuk item ini.');
        }

        $targetBagian = $user->bagian ?? '';
        if ($targetBagian === '') {
            return back()->with('error', 'Data bagian Anda belum diisi. Lengkapi profil (bagian) terlebih dahulu.');
        }

        // Satu request aktif per item (pending/allocated).
        $existing = GoOffer::where('go_action_id', $goAction->id)
            ->where('dbr_index', $dbrIndex)
            ->whereIn('status', ['pending', 'allocated', 'accepted'])
            ->latest()
            ->first();

        if ($existing) {
            return back()->with('error', 'Request untuk item ini sudah ada dan sedang diproses.');
        }

        $snapshot = $item;
        if (empty($snapshot['distribution_type'])) {
            $snapshot['distribution_type'] = 'offer';
        }

        $namaBarang = $item['nama_barang'] ?? '-';

        DB::transaction(function () use ($goAction, $dbrIndex, $snapshot, $targetBagian, $user, $namaBarang) {
            GoOffer::create([
                'go_action_id' => $goAction->id,
                'dbr_index' => $dbrIndex,
                'dbr_snapshot' => $snapshot,
                'offered_by_user_id' => $goAction->user_id,
                'offered_by_bagian' => $goAction->bagian ?? '',
                'target_bagian' => $targetBagian,
                'requested_by_user_id' => $user->id,
                'status' => 'pending',
            ]);

            Notification::create([
                'user_id' => $goAction->user_id,
                'type' => 'go_offer_request',
                'title' => 'Permintaan mutasi barang (DBR)',
                'message' => $user->name.' mengajukan permintaan mutasi untuk barang: '.$namaBarang.'. Silakan setujui atau tolak.',
            ]);

            Notification::create([
                'user_id' => $user->id,
                'type' => 'go_offer_request_sent',
                'title' => 'Permintaan mutasi terkirim',
                'message' => 'Permintaan mutasi untuk "'.$namaBarang.'" telah dikirim ke pemilik entri DBR. Tunggu persetujuan atau penolakan.',
            ]);
        });

        return redirect()->route('go_action.dbr_index')->with('success', 'Permintaan mutasi terkirim. Pemilik entri DBR dan Anda menerima notifikasi.');
    }

    public function accept(Request $request, $id)
    {
        $user = Auth::user();
        $offer = GoOffer::with(['goAction', 'requestedByUser'])->findOrFail($id);

        if ($offer->status !== 'pending') {
            return back()->with('error', 'Request sudah tidak valid.');
        }

        // Hanya creator yang dapat approve.
        if ((int) $offer->goAction->user_id !== (int) $user->id) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menyetujui request ini.');
        }

        $offer->update([
            'status' => 'allocated',
            'accepted_by_user_id' => $user->id,
            'accepted_at' => now(),
        ]);

        if (!empty($offer->requested_by_user_id)) {
            Notification::create([
                'user_id' => $offer->requested_by_user_id,
                'type' => 'go_offer_approved',
                'title' => 'Permintaan mutasi disetujui',
                'message' => 'Pemilik entri DBR menyetujui permintaan mutasi untuk: ' . ($offer->dbr_snapshot['nama_barang'] ?? '-') . '.',
            ]);
        }

        return redirect()->route('go_action.dbr_index')->with('success', 'Permintaan mutasi disetujui. Status item menjadi allocated.');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $offer = GoOffer::with(['goAction'])->findOrFail($id);

        if ($offer->status !== 'pending') {
            return back()->with('error', 'Request sudah tidak valid.');
        }

        // Hanya creator yang dapat reject.
        if ((int) $offer->goAction->user_id !== (int) $user->id) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menolak request ini.');
        }

        $offer->update([
            'status' => 'rejected',
            'accepted_by_user_id' => null,
            'accepted_at' => null,
        ]);

        if (!empty($offer->requested_by_user_id)) {
            Notification::create([
                'user_id' => $offer->requested_by_user_id,
                'type' => 'go_offer_rejected',
                'title' => 'Permintaan mutasi ditolak',
                'message' => 'Pemilik entri DBR menolak permintaan mutasi untuk: ' . ($offer->dbr_snapshot['nama_barang'] ?? '-') . '.',
            ]);
        }

        return redirect()->route('go_action.dbr_index')->with('success', 'Permintaan mutasi ditolak.');
    }

    /**
     * Satu baris DBR dari JSON: kunci indeks bisa int atau string ("0", "1", …).
     */
    private function resolveDbrItem(array $list, int $index): ?array
    {
        if ($list === []) {
            return null;
        }
        if (array_key_exists($index, $list)) {
            $row = $list[$index];

            return is_array($row) ? $row : null;
        }
        $sk = (string) $index;
        if (array_key_exists($sk, $list)) {
            $row = $list[$sk];

            return is_array($row) ? $row : null;
        }

        return null;
    }
}
