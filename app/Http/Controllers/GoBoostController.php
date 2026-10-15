<?php

namespace App\Http\Controllers;

use App\Models\GoBoost;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
     * Tampilkan detail GO BOOST kepada pembuat atau user yang di-mention.
     */
    public function show($id)
    {
        $goBoost = GoBoost::with(['user', 'mentionedUser'])->findOrFail($id);
        $user = Auth::user();

        abort_unless(
            $user->isAdmin()
                || (int) $goBoost->user_id === (int) $user->id
                || (int) $goBoost->mentioned_user_id === (int) $user->id,
            403
        );

        return Inertia::render('GoBoost/Show', [
            'goBoost' => [
                'id' => $goBoost->id,
                'nama_karyawan' => $goBoost->nama_karyawan ?: ($goBoost->user->name ?? 'N/A'),
                'npp_karyawan' => $goBoost->npp_karyawan ?: ($goBoost->user->npp ?? 'N/A'),
                'bagian' => $goBoost->bagian,
                'area_temuan' => $goBoost->area_temuan,
                'ruangan_temuan' => $goBoost->ruangan_temuan,
                'penjelasan_temuan' => $goBoost->penjelasan_temuan,
                'pic_terkait' => $goBoost->pic_terkait,
                'mentioned_user_name' => $goBoost->mentionedUser?->name,
                'status' => $goBoost->status ?? 'OPEN',
                'approval_status' => $goBoost->approval_status,
                'status_perbaikan' => $goBoost->status_perbaikan,
                'keterangan_perbaikan' => $goBoost->keterangan_perbaikan,
                'tanggal_perbaikan' => $goBoost->tanggal_perbaikan?->format('d/m/Y H:i'),
                'created_at' => $goBoost->created_at->format('d/m/Y H:i'),
                'foto_temuan' => $this->photoUrls($goBoost->photo_temuan),
                'foto_perbaikan' => $this->photoUrls($goBoost->foto_perbaikan),
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function photoUrls(?string $photos): array
    {
        if (! $photos) {
            return [];
        }

        $paths = json_decode($photos, true);
        $paths = is_array($paths) ? $paths : [$photos];

        return array_map(fn ($path) => asset('storage/' . $path), $paths);
    }

    /**
     * Tampilkan formulir pembuatan GO BOOST.
     */
    public function create()
    {
        // Ambil daftar semua user untuk dropdown mention
        $users = User::select('id', 'name', 'npp')
            ->where('id', '!=', Auth::user()->id) // Exclude current user (Auth::id() = NPP)
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
            'entry_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'bagian' => 'required|string|max:255',
            'area_temuan' => 'required|string|max:255',
            'ruangan_temuan' => 'required|string|max:255',
            'penjelasan_temuan' => 'required|string',
            'pic_terkait' => 'required|string|max:255',
            'mentioned_user_id' => 'nullable|exists:users,id',
            'photo_temuan' => 'nullable|array|max:5',
            'photo_temuan.*' => 'file|max:10240',
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
            'created_at' => Carbon::parse($validatedData['entry_date'])->setTimeFrom(now()),
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
            'entry_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'keterangan_perbaikan' => 'required|string',
            'foto_perbaikan' => 'nullable|array|max:5',
            'foto_perbaikan.*' => 'file|max:10240',
        ]);

        $photoPaths = [];

        // Unggah foto perbaikan (jika ada)
        if ($request->hasFile('foto_perbaikan')) {
            foreach ($request->file('foto_perbaikan') as $file) {
                $photoPaths[] = $file->store('go_boosts/perbaikan', 'public');
            }
        }

        $entryDate = Carbon::parse($validatedData['entry_date'])->setTimeFrom(now());

        // Update GO BOOST yang sama dengan data perbaikan
        $goBoost->update(array_merge([
            'created_at' => $entryDate,
            'keterangan_perbaikan' => $validatedData['keterangan_perbaikan'],
            'foto_perbaikan' => !empty($photoPaths) ? json_encode($photoPaths) : null,
            'status_perbaikan' => 'selesai',
            'tanggal_perbaikan' => $entryDate,
            'status' => 'CLOSED',
        ], GoBoost::pendingApprovalAttributes()));

        $menungguApproval = GoBoost::hasApprovalWorkflow();

        // Buat notifikasi untuk user yang membuat GO BOOST
        Notification::create([
            'user_id' => $goBoost->user_id,
            'go_boost_id' => $goBoost->id,
            'type' => 'go_boost_perbaikan',
            'title' => $menungguApproval ? 'Perbaikan GO BOOST Selesai (Menunggu Approval)' : 'Perbaikan GO BOOST Selesai',
            'message' => $menungguApproval
                ? $user->name . ' telah menyelesaikan perbaikan untuk GO BOOST di area ' . $goBoost->area_temuan . '. Menunggu approval admin untuk penutupan dan poin.'
                : $user->name . ' telah menyelesaikan perbaikan untuk GO BOOST di area ' . $goBoost->area_temuan . '.',
        ]);

        return back()->with(
            'success',
            $menungguApproval
                ? 'Perbaikan berhasil disubmit dan menunggu approval admin.'
                : 'Perbaikan berhasil disubmit.'
        );
    }
}
