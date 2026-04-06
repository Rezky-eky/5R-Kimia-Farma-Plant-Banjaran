<?php

namespace App\Http\Controllers;

use App\Models\GoAction;
use App\Models\GoSale;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class GoSaleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        $goActions = GoAction::with('user')
            ->whereNotNull('list_barang_ringkas')
            ->latest()
            ->limit(500)
            ->get();

        $goActionIds = $goActions->pluck('id')->values();

        $sales = GoSale::with(['sellerUser', 'buyerUser'])
            ->whereIn('go_action_id', $goActionIds)
            ->whereIn('status', ['pending', 'sold', 'completed', 'rejected', 'cancelled'])
            ->get(['go_action_id', 'dbr_index', 'status', 'id', 'seller_user_id', 'seller_bagian', 'buyer_user_id', 'buyer_bagian', 'agreed_price', 'completed_at', 'created_at']);

        $saleBestByKey = [];
        foreach ($sales as $sale) {
            $key = $sale->go_action_id . '_' . $sale->dbr_index;

            $tracking = 'available';
            $priority = 0;
            if (in_array($sale->status, ['completed', 'sold'], true)) {
                $tracking = 'completed';
                $priority = 3;
            } elseif ($sale->status === 'pending') {
                $tracking = 'requested';
                $priority = 1;
            }

            if (!isset($saleBestByKey[$key]) || $priority > $saleBestByKey[$key]['priority']) {
                $saleBestByKey[$key] = [
                    'tracking' => $tracking,
                    'priority' => $priority,
                    'sale_id' => $sale->id,
                    'seller_user_id' => $sale->seller_user_id,
                    'seller_name' => $sale->sellerUser?->name,
                    'seller_bagian' => $sale->seller_bagian,
                    'buyer_user_id' => $sale->buyer_user_id,
                    'buyer_name' => $sale->buyerUser?->name,
                    'buyer_bagian' => $sale->buyer_bagian,
                    'agreed_price' => $sale->agreed_price,
                    'created_at' => $sale->created_at?->format('d/m/Y H:i'),
                    'completed_at' => $sale->completed_at?->format('d/m/Y H:i'),
                    'raw_status' => $sale->status,
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
                if ($distributionType !== 'sale') {
                    continue;
                }

                $key = $goAction->id . '_' . $index;
                $best = $saleBestByKey[$key] ?? null;
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

                    'distribution_type' => 'sale',
                    'ringkas_status' => $trackingStatus,

                    // Action approval / completion memerlukan id transaksi.
                    'active_sale_id' => in_array($trackingStatus, ['requested', 'completed'], true) ? ($best['sale_id'] ?? null) : null,
                    'active_sale_request_id' => $trackingStatus === 'requested' ? ($best['sale_id'] ?? null) : null,

                    'buyer_user_id' => $best['buyer_user_id'] ?? null,
                    'buyer_name' => $best['buyer_name'] ?? null,
                    'buyer_bagian' => $best['buyer_bagian'] ?? null,
                    'agreed_price' => $best['agreed_price'] ?? null,
                    'requested_created_at' => $best['created_at'] ?? null,
                    'completed_at' => $best['completed_at'] ?? null,
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

        return Inertia::render('GoSale/Index', [
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
            return redirect()->route('go_sale.index')->with('error', 'Item DBR tidak ditemukan.');
        }

        if (($item['distribution_type'] ?? null) !== 'sale') {
            return redirect()->route('go_sale.index')->with('error', 'Item bukan bertipe Sale.');
        }

        // Creator tidak perlu mengajukan request untuk item miliknya.
        if ((int) $goAction->user_id === (int) $user->id) {
            return redirect()->route('go_sale.index')->with('error', 'Anda adalah creator item ini. Gunakan menu untuk menyetujui request.');
        }

        return Inertia::render('GoSale/Create', [
            'goAction' => [
                'id' => $goAction->id,
                'bagian' => $goAction->bagian,
                'list_barang_ringkas' => $goAction->list_barang_ringkas,
            ],
            'dbrIndex' => $dbrIndex,
            'dbrItem' => $item,
            'mode' => 'request',
            'userOptions' => [],
            'departemenOptions' => [],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'go_action_id' => 'required|exists:go_actions,id',
            'dbr_index' => 'required|integer|min:0',
            'mode' => 'required|in:request',
            'agreed_price' => 'required|numeric|min:0',
        ]);

        $goAction = GoAction::findOrFail($validated['go_action_id']);
        $list = $goAction->list_barang_ringkas ?? [];
        $item = $list[$validated['dbr_index']] ?? null;

        if (!$item) {
            return back()->withErrors(['dbr_index' => 'Item DBR tidak ditemukan.'])->withInput();
        }

        if (($item['distribution_type'] ?? null) !== 'sale') {
            return back()->with('error', 'Item bukan bertipe Sale.');
        }

        // Hanya user lain yang boleh melakukan request.
        if ((int) $goAction->user_id === (int) $user->id) {
            return back()->with('error', 'Anda adalah creator item ini. Anda tidak bisa mengajukan request.');
        }

        // Satu transaksi aktif per item (pending/sold/completed).
        $existing = GoSale::where('go_action_id', $goAction->id)
            ->where('dbr_index', $validated['dbr_index'])
            ->whereIn('status', ['pending', 'sold', 'completed'])
            ->latest()
            ->first();

        if ($existing) {
            return back()->with('error', 'Request untuk item ini sudah ada dan sedang diproses.');
        }

        GoSale::create([
            'go_action_id' => $goAction->id,
            'dbr_index' => $validated['dbr_index'],
            'dbr_snapshot' => $item,
            'seller_user_id' => $goAction->user_id, // creator
            'seller_bagian' => $goAction->bagian,
            'buyer_user_id' => $user->id,
            'buyer_bagian' => $user->bagian,
            'agreed_price' => $validated['agreed_price'],
            'status' => 'pending',
        ]);

        Notification::create([
            'user_id' => $goAction->user_id,
            'type' => 'go_sale_request',
            'title' => 'Ada Request Ajukan Beli (Go Sale)',
            'message' => $user->name . ' mengajukan pembelian barang: ' . ($item['nama_barang'] ?? '-') . ' (Rp ' . number_format((float) $validated['agreed_price'], 0, ',', '.') . ').',
        ]);

        return redirect()->route('go_sale.index')->with('success', 'Permintaan pembelian berhasil dibuat. Menunggu respons dari creator.');
    }

    public function accept(Request $request, $id)
    {
        $user = Auth::user();
        $sale = GoSale::with(['goAction'])->findOrFail($id);

        if ($sale->status !== 'pending') {
            return back()->with('error', 'Request sudah tidak valid.');
        }

        // Hanya creator (seller) yang dapat approve.
        if ((int) $sale->seller_user_id !== (int) $user->id) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menyetujui request ini.');
        }

        $sale->update([
            'status' => 'sold',
        ]);

        if (!empty($sale->buyer_user_id)) {
            Notification::create([
                'user_id' => $sale->buyer_user_id,
                'type' => 'go_sale_approved',
                'title' => 'Request Ajukan Beli Disetujui',
                'message' => 'Creator telah menyetujui pembelian barang: ' . ($sale->dbr_snapshot['nama_barang'] ?? '-') . '.',
            ]);
        }

        return redirect()->route('go_sale.index')->with('success', 'Request disetujui. Status item menjadi sold.');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $sale = GoSale::with(['goAction'])->findOrFail($id);

        if ($sale->status !== 'pending') {
            return back()->with('error', 'Request sudah tidak valid.');
        }

        // Hanya creator (seller) yang dapat reject.
        if ((int) $sale->seller_user_id !== (int) $user->id) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menolak request ini.');
        }

        $sale->update([
            'status' => 'rejected',
        ]);

        if (!empty($sale->buyer_user_id)) {
            Notification::create([
                'user_id' => $sale->buyer_user_id,
                'type' => 'go_sale_rejected',
                'title' => 'Request Ajukan Beli Ditolak',
                'message' => 'Creator menolak pembelian barang: ' . ($sale->dbr_snapshot['nama_barang'] ?? '-') . '.',
            ]);
        }

        return redirect()->route('go_sale.index')->with('success', 'Request ditolak. Status item kembali menjadi available.');
    }

    public function complete(Request $request, $id)
    {
        $user = Auth::user();
        $sale = GoSale::findOrFail($id);

        // Buyer hanya bisa menyelesaikan setelah approve (sold).
        if ($sale->status !== 'sold') {
            return back()->with('error', 'Transaksi sudah tidak valid.');
        }

        // Sesuai ownership requirement: hanya creator yang boleh mengubah status akhir.
        if ((int) $sale->seller_user_id !== (int) $user->id) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengubah status transaksi ini.');
        }

        $sale->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return redirect()->route('go_sale.index')->with('success', 'Transaksi diselesaikan.');
    }
}
