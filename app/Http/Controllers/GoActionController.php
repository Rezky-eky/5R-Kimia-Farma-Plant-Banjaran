<?php

namespace App\Http\Controllers;

use App\Models\GoAction;
use App\Models\GoOffer;
use App\Models\GoSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GoActionController extends Controller
{
    /**
     * Tampilkan formulir pembuatan GO ACTION.
     */
    public function create(Request $request)
    {
        return Inertia::render('GoAction/Create', [
            'importedDbrItems' => $request->session()->pull('imported_dbr_items', []),
        ]);
    }

    /**
     * Import item DBR dari file CSV (hasil export/simpan dari Excel).
     */
    public function importDbr(Request $request)
    {
        $validated = $request->validate([
            'excel_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $filePath = $validated['excel_file']->getRealPath();
        if (!$filePath) {
            return back()->with('error', 'File tidak dapat dibaca.');
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return back()->with('error', 'Gagal membuka file CSV.');
        }

        $headers = fgetcsv($handle);
        if (!is_array($headers) || count($headers) === 0) {
            fclose($handle);

            return back()->with('error', 'Header CSV tidak ditemukan.');
        }

        $normalizedHeaders = array_map(
            fn ($h) => $this->normalizeCsvHeader((string) $h),
            $headers
        );

        $items = [];
        $errors = [];
        $rowNumber = 1; // header

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (!is_array($row)) {
                continue;
            }

            $assoc = [];
            foreach ($normalizedHeaders as $index => $header) {
                $assoc[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            if ($this->isCsvRowEmpty($assoc)) {
                continue;
            }

            $namaBarang = $this->csvValue($assoc, ['nama_barang', 'barang', 'nama']);
            $jumlahRaw = $this->csvValue($assoc, ['jumlah', 'qty', 'kuantitas']);
            $satuan = $this->csvValue($assoc, ['satuan', 'unit']) ?: 'Unit';
            $distributionType = strtolower($this->csvValue($assoc, ['distribution_type', 'jenis_distribusi', 'jenis']) ?: 'offer');
            $noAktivaSap = $this->csvValue($assoc, ['no_aktiva_sap', 'no_aktiva', 'sap']);
            $kondisiBarang = strtolower($this->csvValue($assoc, ['kondisi_barang', 'kondisi']) ?: 'baik');
            $statusTps = $this->csvValue($assoc, ['status_tps', 'status_di_tps']) ?: 'Diperlukan';
            $tindakanBarang = $this->csvValue($assoc, ['tindakan_barang', 'tindakan']);

            $jumlah = (int) preg_replace('/[^0-9]/', '', (string) $jumlahRaw);
            if ($jumlah <= 0) {
                $errors[] = "Baris {$rowNumber}: jumlah harus angka minimal 1.";
                continue;
            }

            if (!in_array($distributionType, ['offer', 'sale'], true)) {
                $errors[] = "Baris {$rowNumber}: distribution_type harus offer atau sale.";
                continue;
            }

            if (!in_array($kondisiBarang, ['baik', 'rusak', 'kadaluarsa', 'lainnya'], true)) {
                $errors[] = "Baris {$rowNumber}: kondisi_barang tidak valid.";
                continue;
            }

            if (!in_array($statusTps, ['Diperlukan', 'Ragu-Ragu', 'Tidak Diperlukan'], true)) {
                $errors[] = "Baris {$rowNumber}: status_tps tidak valid.";
                continue;
            }

            if ($namaBarang === '') {
                $errors[] = "Baris {$rowNumber}: nama_barang wajib diisi.";
                continue;
            }

            $items[] = [
                'nama_barang' => $namaBarang,
                'jumlah' => $jumlah,
                'satuan' => $satuan,
                'distribution_type' => $distributionType,
                'no_aktiva_sap' => $noAktivaSap,
                'kondisi_barang' => $kondisiBarang,
                'status_tps' => $statusTps,
                'tindakan_barang' => $tindakanBarang,
            ];
        }

        fclose($handle);

        if (count($errors) > 0) {
            $firstErrors = implode(' | ', array_slice($errors, 0, 3));
            $suffix = count($errors) > 3 ? ' ...' : '';

            return back()->with('error', 'Import gagal. '.$firstErrors.$suffix);
        }

        if (count($items) === 0) {
            return back()->with('error', 'Tidak ada data DBR valid yang bisa diimpor.');
        }

        $request->session()->put('imported_dbr_items', $items);

        return redirect()->route('go_action.create')->with('success', count($items).' item DBR berhasil diimpor dari CSV.');
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
            'list_barang_ringkas.*.distribution_type' => 'required_with:list_barang_ringkas|in:offer,sale',
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

        $goActionIds = $goActions->pluck('id')->values();

        $offers = GoOffer::whereIn('go_action_id', $goActionIds)
            ->get(['go_action_id', 'dbr_index', 'status', 'id', 'created_at']);

        $sales = GoSale::whereIn('go_action_id', $goActionIds)
            ->get(['go_action_id', 'dbr_index', 'status', 'id', 'created_at']);

        // Mapping ringkas_status (available/requested/allocated/completed) per item.
        $offerTrackingByKey = []; // key => ['tracking' => ..., 'priority' => ...]
        foreach ($offers as $offer) {
            $key = $offer->go_action_id . '_' . $offer->dbr_index;
            $raw = $offer->status;

            $tracking = 'available';
            if (in_array($raw, ['allocated', 'accepted'], true)) {
                $tracking = 'allocated';
            } elseif ($raw === 'pending') {
                $tracking = 'requested';
            }

            $priority = match ($tracking) {
                'allocated' => 2,
                'requested' => 1,
                default => 0,
            };

            if (!isset($offerTrackingByKey[$key]) || $priority > $offerTrackingByKey[$key]['priority']) {
                $offerTrackingByKey[$key] = [
                    'tracking' => $tracking,
                    'priority' => $priority,
                    'offer_id' => $offer->id,
                ];
            }
        }

        $saleTrackingByKey = []; // key => ['tracking' => ..., 'priority' => ...]
        foreach ($sales as $sale) {
            $key = $sale->go_action_id . '_' . $sale->dbr_index;
            $raw = $sale->status;

            // sold/completed => completed/sold
            $tracking = 'available';
            if (in_array($raw, ['completed', 'sold'], true)) {
                $tracking = 'completed';
            } elseif ($raw === 'pending') {
                $tracking = 'requested';
            }

            $priority = match ($tracking) {
                'completed' => 3,
                'allocated' => 2,
                'requested' => 1,
                default => 0,
            };

            if (!isset($saleTrackingByKey[$key]) || $priority > $saleTrackingByKey[$key]['priority']) {
                $saleTrackingByKey[$key] = [
                    'tracking' => $tracking,
                    'priority' => $priority,
                    'sale_id' => $sale->id,
                ];
            }
        }

        $priorityMap = [
            'available' => 0,
            'requested' => 1,
            'allocated' => 2,
            'completed' => 3,
        ];
        $reversePriority = ['available', 'requested', 'allocated', 'completed'];

        $dbrItems = [];
        foreach ($goActions as $goAction) {
            if (is_array($goAction->list_barang_ringkas)) {
                foreach ($goAction->list_barang_ringkas as $index => $barang) {
                    $key = $goAction->id . '_' . $index;
                    $distributionType = $barang['distribution_type'] ?? null;
                    // DBR lama tanpa kolom ini tetap boleh di-request (dianggap eligible mutasi).
                    $mutationEligible = $distributionType === null
                        || $distributionType === ''
                        || in_array($distributionType, ['offer', 'sale'], true);

                    // Gabung status mutasi dari Go Offer + Go Sale (legacy) agar satu kolom "Offer" konsisten.
                    $offerTrack = $offerTrackingByKey[$key]['tracking'] ?? 'available';
                    $saleTrack = $saleTrackingByKey[$key]['tracking'] ?? 'available';
                    $maxP = max($priorityMap[$offerTrack] ?? 0, $priorityMap[$saleTrack] ?? 0);
                    $trackingStatus = $reversePriority[$maxP] ?? 'available';

                    $activeOfferId = (($offerTrackingByKey[$key]['tracking'] ?? '') === 'requested')
                        ? ($offerTrackingByKey[$key]['offer_id'] ?? null)
                        : null;
                    $activeSaleRequestId = (($saleTrackingByKey[$key]['tracking'] ?? '') === 'requested')
                        ? ($saleTrackingByKey[$key]['sale_id'] ?? null)
                        : null;

                    $dbrItems[] = [
                        'id' => $goAction->id . '_' . $index,
                        'go_action_id' => $goAction->id,
                        'dbr_index' => (int) $index,
                        'tanggal' => $goAction->created_at->format('d/m/Y H:i'),
                        'bagian' => $goAction->bagian,
                        'nama_ruangan' => $goAction->nama_ruangan ?? '-',
                        'nama_barang' => $barang['nama_barang'] ?? '-',
                        'jumlah' => $barang['jumlah'] ?? 0,
                        'satuan' => $barang['satuan'] ?? '-',
                        'distribution_type' => $distributionType,
                        'mutation_eligible' => $mutationEligible,
                        'no_aktiva_sap' => $barang['no_aktiva_sap'] ?? '-',
                        'status_tps' => $barang['status_tps'] ?? '-',
                        'tindakan_barang' => $barang['tindakan_barang'] ?? '-',
                        'kondisi_barang' => $barang['kondisi_barang'] ?? '-',
                        'pelapor' => $goAction->user->name ?? 'N/A',
                        'creator_user_id' => $goAction->user_id,
                        'ringkas_status' => $trackingStatus,
                        'active_offer_id' => $activeOfferId,
                        'active_sale_request_id' => $activeSaleRequestId,
                    ];
                }
            }
        }

        return Inertia::render('GoAction/DBRIndex', [
            'dbrItems' => $dbrItems,
            'userBagian' => Auth::user()->bagian ?? null,
        ]);
    }

    private function normalizeCsvHeader(string $header): string
    {
        $normalized = Str::of($header)
            ->lower()
            ->trim()
            ->replace(['.', '-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();

        return $normalized;
    }

    private function isCsvRowEmpty(array $assoc): bool
    {
        foreach ($assoc as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function csvValue(array $assoc, array $aliases): string
    {
        foreach ($aliases as $key) {
            if (array_key_exists($key, $assoc)) {
                return trim((string) $assoc[$key]);
            }
        }

        return '';
    }
}
