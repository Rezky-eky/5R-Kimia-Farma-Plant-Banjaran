<?php

namespace App\Http\Controllers;

use App\Models\GoAction;
use App\Models\GoSale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GoSaleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        $query = GoSale::with(['goAction', 'sellerUser', 'buyerUser'])->latest();

        if (!$isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('seller_user_id', $user->id)
                    ->orWhere('buyer_user_id', $user->id)
                    ->orWhere('seller_bagian', $user->bagian)
                    ->orWhere('buyer_bagian', $user->bagian);
            });
        }

        $sales = $query->paginate(15)->through(function ($sale) {
            return [
                'id' => $sale->id,
                'go_action_id' => $sale->go_action_id,
                'dbr_index' => $sale->dbr_index,
                'dbr_snapshot' => $sale->dbr_snapshot,
                'seller_user_id' => $sale->seller_user_id,
                'seller_bagian' => $sale->seller_bagian,
                'seller_name' => $sale->sellerUser?->name,
                'buyer_user_id' => $sale->buyer_user_id,
                'buyer_bagian' => $sale->buyer_bagian,
                'buyer_name' => $sale->buyerUser?->name,
                'agreed_price' => $sale->agreed_price,
                'status' => $sale->status,
                'completed_at' => $sale->completed_at?->format('d/m/Y H:i'),
                'created_at' => $sale->created_at->format('d/m/Y H:i'),
            ];
        });

        return Inertia::render('GoSale/Index', [
            'sales' => $sales,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $goActionId = $request->get('go_action_id');
        $dbrIndex = (int) $request->get('dbr_index', 0);
        $mode = $request->get('mode', 'request');

        $goAction = GoAction::findOrFail($goActionId);
        $list = $goAction->list_barang_ringkas ?? [];
        $item = $list[$dbrIndex] ?? null;

        if (!$item) {
            return redirect()->route('go_action.dbr_index')->with('error', 'Item DBR tidak ditemukan.');
        }

        if ($mode === 'mention') {
            if ($goAction->bagian !== $user->bagian) {
                return redirect()->route('go_action.dbr_index')->with('error', 'Sale ke User hanya untuk barang dari bagian Anda.');
            }
            $userOptions = User::where('id', '!=', $user->id)
                ->whereNotNull('bagian')
                ->orderBy('name')
                ->get(['id', 'name', 'bagian'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'bagian' => $u->bagian ?? '-', 'label' => $u->name . ' (' . ($u->bagian ?? '-') . ')'])
                ->values()
                ->all();

            return Inertia::render('GoSale/Create', [
                'goAction' => [
                    'id' => $goAction->id,
                    'bagian' => $goAction->bagian,
                    'list_barang_ringkas' => $goAction->list_barang_ringkas,
                ],
                'dbrIndex' => $dbrIndex,
                'dbrItem' => $item,
                'mode' => 'mention',
                'userOptions' => $userOptions,
                'departemenOptions' => [],
            ]);
        }

        if ($goAction->bagian === $user->bagian) {
            return redirect()->route('go_action.dbr_index')->with('error', 'Barang dari bagian Anda tidak bisa diminta. Gunakan "Sale ke User".');
        }

        $departemenOptions = [
            'Bagian Mekanik & Electrical',
            'Bagian Pemastian Operasional',
            'Bagian Pemenuhan Regulasi',
            'Bagian Pendukung Teknis',
            'Bagian Pengadaan Barang Operasional',
            'Bagian Pengawasan Mutu',
            'Bagian Pengemasan Farma',
            'Bagian Pengendalian Proses Produksi',
            'Bagian Pengendalian Sistem',
            'Bagian Penyimpanan',
            'Bagian Produksi I',
            'Bagian Produksi II',
            'Bagian Produksi III',
            'Bagian SDM & Akuntansi',
            'Bagian Umum dan K3L',
            'Bagian Utility',
            'IT Support Plant',
            'Lainnya'
        ];

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
            'departemenOptions' => $departemenOptions,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'go_action_id' => 'required|exists:go_actions,id',
            'dbr_index' => 'required|integer|min:0',
            'mode' => 'required|in:request,mention',
            'buyer_bagian' => 'nullable|string|max:255',
            'buyer_user_id' => 'nullable|exists:users,id',
            'agreed_price' => 'required|numeric|min:0',
        ]);

        $goAction = GoAction::findOrFail($validated['go_action_id']);
        $list = $goAction->list_barang_ringkas ?? [];
        $item = $list[$validated['dbr_index']] ?? null;

        if (!$item) {
            return back()->withErrors(['dbr_index' => 'Item DBR tidak ditemukan.'])->withInput();
        }

        if ($validated['mode'] === 'request') {
            if ($goAction->bagian === $user->bagian) {
                return back()->with('error', 'Barang dari bagian Anda tidak bisa diminta. Gunakan "Sale ke User".');
            }
            GoSale::create([
                'go_action_id' => $goAction->id,
                'dbr_index' => $validated['dbr_index'],
                'dbr_snapshot' => $item,
                'seller_user_id' => $goAction->user_id,
                'seller_bagian' => $goAction->bagian,
                'buyer_user_id' => $user->id,
                'buyer_bagian' => $user->bagian,
                'agreed_price' => $validated['agreed_price'],
                'status' => 'pending',
            ]);
            return redirect()->route('go_sale.index')->with('success', 'Permintaan pembelian berhasil dibuat. Menunggu konfirmasi dari pemilik barang.');
        }

        if ($goAction->bagian !== $user->bagian) {
            return back()->with('error', 'Sale ke User hanya untuk barang dari bagian Anda.');
        }
        if (empty($validated['buyer_user_id'])) {
            return back()->withErrors(['buyer_user_id' => 'Pilih user pembeli.'])->withInput();
        }
        $buyerUser = User::findOrFail($validated['buyer_user_id']);

        GoSale::create([
            'go_action_id' => $goAction->id,
            'dbr_index' => $validated['dbr_index'],
            'dbr_snapshot' => $item,
            'seller_user_id' => $user->id,
            'seller_bagian' => $user->bagian,
            'buyer_user_id' => $buyerUser->id,
            'buyer_bagian' => $buyerUser->bagian ?? '',
            'agreed_price' => $validated['agreed_price'],
            'status' => 'pending',
        ]);

        return redirect()->route('go_sale.index')->with('success', 'Penawaran jual ke user berhasil dibuat.');
    }

    public function complete(Request $request, $id)
    {
        $user = Auth::user();
        $sale = GoSale::findOrFail($id);

        if ($sale->status !== 'pending') {
            return back()->with('error', 'Transaksi sudah tidak valid.');
        }

        if ($sale->buyer_user_id) {
            if ($sale->buyer_user_id !== $user->id) {
                return back()->with('error', 'Hanya user pembeli yang ditunjuk yang dapat menyelesaikan transaksi.');
            }
        } elseif ($sale->buyer_bagian !== $user->bagian) {
            return back()->with('error', 'Hanya pembeli (departemen tujuan) yang dapat menyelesaikan transaksi.');
        }

        $sale->update([
            'status' => 'completed',
            'buyer_user_id' => $user->id,
            'completed_at' => now(),
        ]);

        return redirect()->route('go_sale.index')->with('success', 'Transaksi diselesaikan.');
    }
}
