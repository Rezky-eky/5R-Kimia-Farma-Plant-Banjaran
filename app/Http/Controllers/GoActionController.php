<?php

namespace App\Http\Controllers;

use App\Models\GoAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GoActionController extends Controller
{
    /**
     * Tampilkan formulir pembuatan GO ACTION.
     */
    public function create()
    {
        return Inertia::render('GoAction/Create');
    }

    /**
     * Simpan data GO ACTION yang baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Dapatkan user yang sedang login
        $user = Auth::user();

        // 2. Validasi Input menggunakan Form Request
        $validatedData = $request->validate([
            'nama_karyawan' => 'required|string|max:255',
            'bagian' => 'required|string|max:255',
            'nama_ruangan' => 'nullable|string|max:255',
            'penjelasan_aksi' => 'nullable|string',
            'foto_kegiatan' => 'nullable|array|max:5',
            'foto_kegiatan.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'list_barang_ringkas' => 'nullable|array',
            'list_barang_ringkas.*.nama_barang' => 'required_with:list_barang_ringkas|string|max:255',
            'list_barang_ringkas.*.jumlah' => 'required_with:list_barang_ringkas|integer|min:1',
            'list_barang_ringkas.*.satuan' => 'required_with:list_barang_ringkas|string|max:50',
            'list_barang_ringkas.*.no_aktiva_sap' => 'nullable|string|max:100',
            'list_barang_ringkas.*.kondisi_barang' => 'required_with:list_barang_ringkas|in:baik,rusak,kadaluarsa,lainnya',
            'list_barang_ringkas.*.status_tps' => 'required_with:list_barang_ringkas|in:Diperlukan,Ragu-Ragu,Tidak Diperlukan',
            'list_barang_ringkas.*.tindakan_barang' => 'nullable|string|max:500',
        ]);

        // Validasi fleksibilitas: minimal salah satu (Foto/Aksi ATAU DBR)
        $hasFotoAksi = $request->hasFile('foto_kegiatan') && count($request->file('foto_kegiatan', [])) > 0;
        $hasPenjelasan = !empty($validatedData['penjelasan_aksi']);
        $hasDBR = !empty($validatedData['list_barang_ringkas']) && is_array($validatedData['list_barang_ringkas']) && count($validatedData['list_barang_ringkas']) > 0;

        if (!$hasFotoAksi && !$hasPenjelasan && !$hasDBR) {
            return back()->withErrors([
                'foto_kegiatan' => 'Minimal salah satu harus diisi: Foto/Aksi ATAU Daftar Barang Ringkas.'
            ])->withInput();
        }

        $fotoPaths = [];

        // 3. Unggah Foto Kegiatan
        if ($request->hasFile('foto_kegiatan')) {
            foreach ($request->file('foto_kegiatan') as $file) {
                // Simpan gambar di dalam folder 'go_actions' di disk 'public'
                $fotoPaths[] = $file->store('go_actions', 'public');
            }
        }

        // 4. Simpan data ke Database dengan user_id dan npp_karyawan dari user yang login
        $goAction = GoAction::create([
            'user_id' => $user->id,
            'npp_karyawan' => $user->npp,
            'nama_karyawan' => $validatedData['nama_karyawan'],
            'metode' => 'GO ACTION',
            'bagian' => $validatedData['bagian'],
            'nama_ruangan' => $validatedData['nama_ruangan'] ?? null,
            'kode_ruangan' => null,
            'penjelasan_aksi' => $validatedData['penjelasan_aksi'] ?? null,
            'foto_kegiatan_path' => !empty($fotoPaths) ? json_encode($fotoPaths) : null,
            'latitude' => $validatedData['latitude'],
            'longitude' => $validatedData['longitude'],
            'list_barang_ringkas' => $validatedData['list_barang_ringkas'] ?? null,
        ]);

        // 5. Redirect dengan Flash Message Sukses
        return redirect()->route('dashboard')->with('success', 'Data GO ACTION berhasil disimpan.');
    }

    /**
     * Tampilkan daftar semua DBR dari Go Action.
     */
    /**
     * Tampilkan daftar semua DBR dari Go Action.
     */
    public function dbrIndex()
    {
        $goActions = GoAction::with('user')
            ->whereNotNull('list_barang_ringkas')
            ->latest()
            ->get();

        $dbrItems = [];
        foreach ($goActions as $goAction) {
            if (is_array($goAction->list_barang_ringkas)) {
                foreach ($goAction->list_barang_ringkas as $index => $barang) {
                    $dbrItems[] = [
                        'id' => $goAction->id . '_' . $index,
                        'go_action_id' => $goAction->id,
                        'dbr_index' => $index,
                        'tanggal' => $goAction->created_at->format('d/m/Y H:i'),
                        'bagian' => $goAction->bagian,
                        'nama_ruangan' => $goAction->nama_ruangan ?? '-',
                        'nama_barang' => $barang['nama_barang'] ?? '-',
                        'jumlah' => $barang['jumlah'] ?? 0,
                        'satuan' => $barang['satuan'] ?? '-',
                        'no_aktiva_sap' => $barang['no_aktiva_sap'] ?? '-',
                        'status_tps' => $barang['status_tps'] ?? '-',
                        'tindakan_barang' => $barang['tindakan_barang'] ?? '-',
                        'kondisi_barang' => $barang['kondisi_barang'] ?? '-',
                        'pelapor' => $goAction->user->name ?? 'N/A',
                    ];
                }
            }
        }

        return Inertia::render('GoAction/DBRIndex', [
            'dbrItems' => $dbrItems,
            'userBagian' => Auth::user()->bagian ?? null,
        ]);
    }
}
