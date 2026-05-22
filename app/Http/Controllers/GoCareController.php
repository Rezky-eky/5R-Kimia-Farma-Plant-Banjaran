<?php

namespace App\Http\Controllers;

use App\Models\GoCare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GoCareController extends Controller
{
    /**
     * Tampilkan formulir pembuatan GO CARE.
     */
    public function create()
    {
        return Inertia::render('GoCare/Create');
    }

    /**
     * Simpan data GO CARE yang baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Dapatkan user yang sedang login
        $user = Auth::user();

        // 2. Validasi Input
        $validatedData = $request->validate([
            'nama_karyawan' => 'nullable|string|max:255',
            'npp_karyawan' => 'nullable|string|max:255',
            'bagian' => 'required|string|max:255',
            'bagian_temuan' => 'required|string|max:255',
            'area_temuan' => 'nullable|string|max:255',
            'penjelasan_temuan' => 'required|string',
            'photo_before' => 'required|array|min:1|max:5',
            'photo_before.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            'penjelasan_capa' => 'required|string',
            'photo_after' => 'required|array|min:1|max:5',
            'photo_after.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $photoBeforePaths = [];
        $photoAfterPaths = [];

        // 3. Unggah Foto Before (jika ada)
        if ($request->hasFile('photo_before')) {
            foreach ($request->file('photo_before') as $file) {
                // Simpan gambar di dalam folder 'go_cares' di disk 'public'
                $photoBeforePaths[] = $file->store('go_cares', 'public');
            }
        }

        // 4. Unggah Foto After (jika ada)
        if ($request->hasFile('photo_after')) {
            foreach ($request->file('photo_after') as $file) {
                // Simpan gambar di dalam folder 'go_cares' di disk 'public'
                $photoAfterPaths[] = $file->store('go_cares', 'public');
            }
        }

        // 5. Simpan data ke Database (hanya kolom yang ada di tabel go_cares)
        $data = array_merge([
            'user_id' => $user->id,
            'bagian_temuan' => $validatedData['bagian_temuan'],
            'penjelasan_temuan' => $validatedData['penjelasan_temuan'],
            'photo_before' => !empty($photoBeforePaths) ? json_encode($photoBeforePaths) : null,
            'penjelasan_capa' => $validatedData['penjelasan_capa'],
            'photo_after' => !empty($photoAfterPaths) ? json_encode($photoAfterPaths) : null,
        ], GoCare::pendingApprovalAttributes());

        if (Schema::hasColumn('go_cares', 'nama_karyawan')) {
            $data['nama_karyawan'] = $user->name;
        }
        if (Schema::hasColumn('go_cares', 'npp_karyawan')) {
            $data['npp_karyawan'] = $user->npp;
        }
        if (Schema::hasColumn('go_cares', 'bagian')) {
            $data['bagian'] = $validatedData['bagian'];
        }
        if (Schema::hasColumn('go_cares', 'area_temuan')) {
            $data['area_temuan'] = $validatedData['area_temuan'] ?? null;
        }
        GoCare::create($data);

        // 6. Redirect dengan Flash Message Sukses
        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                GoCare::hasApprovalWorkflow()
                    ? 'Data GO CARE berhasil disimpan dan menunggu approval admin.'
                    : 'Data GO CARE berhasil disimpan.'
            );
    }
}
