<?php

namespace App\Http\Controllers;

use App\Models\GoBoost;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GoBoostController extends Controller
{
    /**
     * Tampilkan daftar semua GO BOOST.
     */
    public function index()
    {
        $goBoosts = GoBoost::with('user')
            ->latest()
            ->get()
            ->map(function ($goBoost) {
                return [
                    'id' => $goBoost->id,
                    'area_temuan' => $goBoost->area_temuan,
                    'ruangan_temuan' => $goBoost->ruangan_temuan,
                    'penjelasan_temuan' => $goBoost->penjelasan_temuan,
                    'pic_terkait' => $goBoost->pic_terkait,
                    'photo_temuan' => $goBoost->photo_temuan ? asset('storage/' . $goBoost->photo_temuan) : null,
                    'status' => $goBoost->status ?? 'OPEN', // Default to OPEN if status doesn't exist
                    'created_at' => $goBoost->created_at->format('d/m/Y H:i'),
                    'user_name' => $goBoost->user->name ?? 'N/A',
                ];
            });

        return Inertia::render('GoBoost/Index', [
            'goBoosts' => $goBoosts,
        ]);
    }

    /**
     * Tampilkan formulir pembuatan GO BOOST.
     */
    public function create()
    {
        // Ambil daftar semua user untuk dropdown mention
        $users = User::select('id', 'name', 'npp')
            ->where('id', '!=', Auth::id()) // Exclude current user
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'npp' => $user->npp,
                    'label' => $user->name . ' (' . $user->npp . ')',
                ];
            });

        return Inertia::render('GoBoost/Create', [
            'users' => $users,
        ]);
    }

    /**
     * Simpan data GO BOOST yang baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Dapatkan user yang sedang login
        $user = Auth::user();

        // Pastikan user memiliki NPP
        if (empty($user->npp)) {
            // Generate NPP jika belum ada
            $user->npp = $this->generateNPP($user);
            $user->save();
        }

        // 2. Validasi Input
        $validatedData = $request->validate([
            'nama_karyawan' => 'nullable|string|max:255',
            'npp_karyawan' => 'nullable|string|max:255',
            'bagian' => 'required|string|max:255',
            'area_temuan' => 'required|string|max:255',
            'ruangan_temuan' => 'required|string|max:255',
            'penjelasan_temuan' => 'required|string',
            'pic_terkait' => 'required|string|max:255',
            'mentioned_user_id' => 'nullable|exists:users,id',
            'photo_temuan' => 'nullable|array|max:5',
            'photo_temuan.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $photoPaths = [];

        // 3. Unggah Foto Temuan (jika ada)
        if ($request->hasFile('photo_temuan')) {
            foreach ($request->file('photo_temuan') as $file) {
                // Simpan gambar di dalam folder 'go_boosts' di disk 'public'
                $photoPaths[] = $file->store('go_boosts', 'public');
            }
        }

        // 4. Simpan data ke Database dengan user_id dari user yang login
        $goBoost = GoBoost::create([
            'user_id' => $user->id,
            'mentioned_user_id' => $validatedData['mentioned_user_id'] ?? null,
            'nama_karyawan' => $validatedData['nama_karyawan'] ?? $user->name,
            'npp_karyawan' => $validatedData['npp_karyawan'] ?? $user->npp,
            'bagian' => $validatedData['bagian'],
            'area_temuan' => $validatedData['area_temuan'],
            'ruangan_temuan' => $validatedData['ruangan_temuan'],
            'penjelasan_temuan' => $validatedData['penjelasan_temuan'],
            'pic_terkait' => $validatedData['pic_terkait'],
            'photo_temuan' => !empty($photoPaths) ? json_encode($photoPaths) : null,
        ]);

        // 5. Buat notifikasi jika ada user yang di-mention
        if (!empty($validatedData['mentioned_user_id'])) {
            $mentionedUser = User::find($validatedData['mentioned_user_id']);
            if ($mentionedUser) {
                Notification::create([
                    'user_id' => $mentionedUser->id,
                    'go_boost_id' => $goBoost->id,
                    'type' => 'go_boost_mention',
                    'title' => 'Anda di-mention di GO BOOST',
                    'message' => $user->name . ' telah mention Anda dalam GO BOOST di area ' . $validatedData['area_temuan'] . '.',
                ]);
            }
        }

        // 6. Redirect dengan Flash Message Sukses
        return redirect()->route('dashboard')->with('success', 'Data GO BOOST berhasil disimpan.');
    }

    /**
     * Generate unique NPP for user.
     */
    private function generateNPP($user): string
    {
        do {
            // Generate 10-digit NPP (format: YYYYMMDD + random 2 digits)
            $npp = date('Ymd') . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        } while (User::where('npp', $npp)->where('id', '!=', $user->id)->exists());

        return $npp;
    }

    /**
     * Submit perbaikan untuk GO BOOST yang di-mention.
     */
    public function submitPerbaikan(Request $request, $id)
    {
        $user = Auth::user();
        $goBoost = GoBoost::findOrFail($id);

        // Pastikan user adalah yang di-mention
        if ($goBoost->mentioned_user_id !== $user->id) {
            return back()->withErrors(['error' => 'Anda tidak memiliki izin untuk melakukan perbaikan ini.']);
        }

        // Validasi input
        $validatedData = $request->validate([
            'keterangan_perbaikan' => 'required|string',
            'foto_perbaikan' => 'nullable|array|max:5',
            'foto_perbaikan.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $photoPaths = [];

        // Unggah foto perbaikan (jika ada)
        if ($request->hasFile('foto_perbaikan')) {
            foreach ($request->file('foto_perbaikan') as $file) {
                $photoPaths[] = $file->store('go_boosts/perbaikan', 'public');
            }
        }

        // Update GO BOOST dengan data perbaikan
        $goBoost->update([
            'keterangan_perbaikan' => $validatedData['keterangan_perbaikan'],
            'foto_perbaikan' => !empty($photoPaths) ? json_encode($photoPaths) : null,
            'status_perbaikan' => 'selesai',
            'tanggal_perbaikan' => now(),
            'status' => 'CLOSED',
        ]);

        // Tambahkan poin untuk booster (pembuat GO BOOST) dan fixer (user yang melakukan perbaikan)
        // Booster mendapatkan 10 poin ketika perbaikan selesai
        if ($goBoost->user) {
            $goBoost->user->increment('points_balance', 10);
        }

        // User yang melakukan perbaikan juga mendapatkan 10 poin
        $user->increment('points_balance', 10);

        // Buat notifikasi untuk user yang membuat GO BOOST
        Notification::create([
            'user_id' => $goBoost->user_id,
            'go_boost_id' => $goBoost->id,
            'type' => 'go_boost_perbaikan',
            'title' => 'Perbaikan GO BOOST Selesai',
            'message' => $user->name . ' telah menyelesaikan perbaikan untuk GO BOOST di area ' . $goBoost->area_temuan . '.',
        ]);

        return back()->with('success', 'Perbaikan berhasil disubmit. Terima kasih! Anda dan booster masing-masing mendapat 10 poin.');
    }
}
