<?php

namespace App\Http\Controllers;

use App\Models\GoAction;
use App\Models\GoOffer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class GoOfferController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        $query = GoOffer::with(['goAction', 'offeredByUser', 'acceptedByUser', 'targetUser', 'requestedByUser'])->latest();

        if (!$isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('offered_by_user_id', $user->id)
                    ->orWhere('target_bagian', $user->bagian);

                // Tambahan filter hanya jika kolom sudah ada di database
                if (Schema::hasColumn('go_offers', 'target_user_id')) {
                    $q->orWhere('target_user_id', $user->id);
                }
                if (Schema::hasColumn('go_offers', 'requested_by_user_id')) {
                    $q->orWhere('requested_by_user_id', $user->id);
                }
            });
        }

        $offers = $query->paginate(15)->through(function ($offer) {
            return [
                'id' => $offer->id,
                'go_action_id' => $offer->go_action_id,
                'dbr_index' => $offer->dbr_index,
                'dbr_snapshot' => $offer->dbr_snapshot,
                'offered_by_user_id' => $offer->offered_by_user_id,
                'offered_by_bagian' => $offer->offered_by_bagian,
                'offered_by_name' => $offer->offeredByUser?->name,
                'target_bagian' => $offer->target_bagian,
                'target_user_id' => $offer->target_user_id,
                'target_user_name' => $offer->targetUser?->name,
                'requested_by_user_id' => $offer->requested_by_user_id,
                'requested_by_name' => $offer->requestedByUser?->name,
                'status' => $offer->status,
                'accepted_by_user_id' => $offer->accepted_by_user_id,
                'accepted_at' => $offer->accepted_at?->format('d/m/Y H:i'),
                'created_at' => $offer->created_at->format('d/m/Y H:i'),
            ];
        });

        return Inertia::render('GoOffer/Index', [
            'offers' => $offers,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $goActionId = $request->get('go_action_id');
        $dbrIndex = (int) $request->get('dbr_index', 0);
        $mode = $request->get('mode', 'request'); // request = meminta dari bagian lain, mention = tawarkan ke user (barang bagian sendiri)

        $goAction = GoAction::findOrFail($goActionId);
        $list = $goAction->list_barang_ringkas ?? [];
        $item = $list[$dbrIndex] ?? null;

        if (!$item) {
            return redirect()->route('go_action.dbr_index')->with('error', 'Item DBR tidak ditemukan.');
        }

        if ($mode === 'mention') {
            // Barang dari bagian sendiri: pilih user (nama + bagian) untuk ditawari
            if ($goAction->bagian !== $user->bagian) {
                return redirect()->route('go_action.dbr_index')->with('error', 'Offer ke User hanya untuk barang dari bagian Anda.');
            }
            $userOptions = User::where('id', '!=', $user->id)
                ->whereNotNull('bagian')
                ->orderBy('name')
                ->get(['id', 'name', 'bagian'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'bagian' => $u->bagian ?? '-', 'label' => $u->name . ' (' . ($u->bagian ?? '-') . ')'])
                ->values()
                ->all();

            return Inertia::render('GoOffer/Create', [
                'goAction' => [
                    'id' => $goAction->id,
                    'bagian' => $goAction->bagian,
                    'list_barang_ringkas' => $goAction->list_barang_ringkas,
                ],
                'dbrIndex' => $dbrIndex,
                'dbrItem' => $item,
                'mode' => 'mention',
                'userOptions' => $userOptions,
                'targetOptions' => [],
            ]);
        }

        // mode = request: meminta barang dari bagian lain (target = bagian saya)
        if ($goAction->bagian === $user->bagian) {
            return redirect()->route('go_action.dbr_index')->with('error', 'Barang dari bagian Anda tidak bisa diminta. Gunakan "Offer ke User" untuk menawarkan ke rekan.');
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
        $targetOptions = array_filter($departemenOptions, fn ($b) => $b !== $goAction->bagian);

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
            'targetOptions' => array_values($targetOptions),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'go_action_id' => 'required|exists:go_actions,id',
            'dbr_index' => 'required|integer|min:0',
            'mode' => 'required|in:request,mention',
            'target_bagian' => 'nullable|string|max:255',
            'target_user_id' => 'nullable|exists:users,id',
        ]);

        $goAction = GoAction::findOrFail($validated['go_action_id']);
        $list = $goAction->list_barang_ringkas ?? [];
        $item = $list[$validated['dbr_index']] ?? null;

        if (!$item) {
            return back()->withErrors(['dbr_index' => 'Item DBR tidak ditemukan.'])->withInput();
        }

        if ($validated['mode'] === 'request') {
            // Meminta barang dari bagian lain: hanya jika barang dari bagian lain
            if ($goAction->bagian === $user->bagian) {
                return back()->with('error', 'Barang dari bagian Anda tidak bisa diminta. Gunakan "Offer ke User".');
            }
            $targetBagian = $user->bagian ?? '';
            if ($targetBagian === '') {
                return back()->with('error', 'Data bagian Anda belum diisi. Lengkapi profil (bagian) terlebih dahulu.');
            }
            $offer = GoOffer::create([
                'go_action_id' => $goAction->id,
                'dbr_index' => $validated['dbr_index'],
                'dbr_snapshot' => $item,
                'offered_by_user_id' => $goAction->user_id,
                'offered_by_bagian' => $goAction->bagian ?? '',
                'target_bagian' => $targetBagian,
                'requested_by_user_id' => $user->id,
                'status' => 'pending',
            ]);
            return redirect()->route('go_offer.index')->with('success', 'Permintaan tawaran berhasil dibuat. Menunggu respons dari bagian pemilik barang.');
        }

        // mode = mention: menawarkan barang (dari bagian sendiri) ke user tertentu
        if ($goAction->bagian !== $user->bagian) {
            return back()->with('error', 'Offer ke User hanya untuk barang dari bagian Anda.');
        }
        if (empty($validated['target_user_id'])) {
            return back()->withErrors(['target_user_id' => 'Pilih user yang akan ditawari.'])->withInput();
        }
        $targetUser = User::findOrFail($validated['target_user_id']);

        $offer = GoOffer::create([
            'go_action_id' => $goAction->id,
            'dbr_index' => $validated['dbr_index'],
            'dbr_snapshot' => $item,
            'offered_by_user_id' => $user->id,
            'offered_by_bagian' => $user->bagian,
            'target_bagian' => $targetUser->bagian ?? '',
            'target_user_id' => $targetUser->id,
            'status' => 'pending',
        ]);

        return redirect()->route('go_offer.index')->with('success', 'Tawaran ke user berhasil dibuat.');
    }

    public function accept(Request $request, $id)
    {
        $user = Auth::user();
        $offer = GoOffer::with('goAction')->findOrFail($id);

        if ($offer->status !== 'pending') {
            return back()->with('error', 'Tawaran sudah tidak valid.');
        }

        if ($offer->target_user_id) {
            if ($offer->target_user_id !== $user->id) {
                return back()->with('error', 'Hanya user yang ditunjuk yang dapat menerima tawaran ini.');
            }
        } elseif ($offer->target_bagian !== $user->bagian) {
            return back()->with('error', 'Hanya departemen tujuan yang dapat menerima tawaran.');
        }

        $offer->update([
            'status' => 'accepted',
            'accepted_by_user_id' => $user->id,
            'accepted_at' => now(),
        ]);

        return redirect()->route('go_offer.index')->with('success', 'Tawaran diterima.');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $offer = GoOffer::findOrFail($id);

        if ($offer->status !== 'pending') {
            return back()->with('error', 'Tawaran sudah tidak valid.');
        }

        if ($offer->target_user_id) {
            if ($offer->target_user_id !== $user->id) {
                return back()->with('error', 'Hanya user yang ditunjuk yang dapat menolak tawaran ini.');
            }
        } elseif ($offer->target_bagian !== $user->bagian) {
            return back()->with('error', 'Hanya departemen tujuan yang dapat menolak tawaran.');
        }

        $offer->update(['status' => 'rejected']);

        return redirect()->route('go_offer.index')->with('success', 'Tawaran ditolak.');
    }
}
