<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DbrImportService
{
    /**
     * @return array{items: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function parseUploadedFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $realPath = $file->getRealPath();
        if (!$realPath) {
            return ['items' => [], 'errors' => ['File tidak dapat dibaca.']];
        }

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->parseCsvPath($realPath);
        }

        return $this->parseExcelPath($realPath);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function parseCsvPath(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return ['items' => [], 'errors' => ['Gagal membuka file CSV.']];
        }

        $headers = fgetcsv($handle);
        if (!is_array($headers) || count($headers) === 0) {
            fclose($handle);

            return ['items' => [], 'errors' => ['Header CSV tidak ditemukan.']];
        }

        $normalizedHeaders = array_map(
            fn ($h) => $this->normalizeHeader((string) $h),
            $headers
        );

        $matrix = [];
        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (!is_array($row)) {
                continue;
            }
            $assoc = [];
            foreach ($normalizedHeaders as $index => $header) {
                $assoc[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }
            if ($this->isRowEmpty($assoc)) {
                continue;
            }
            $matrix[] = ['row' => $rowNumber, 'data' => $assoc];
        }
        fclose($handle);

        return $this->validateMatrixRows($matrix);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function parseExcelPath(string $path): array
    {
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            return ['items' => [], 'errors' => ['Gagal membaca file Excel. Pastikan format .xlsx valid dan extension PHP zip aktif di server.']];
        }

        $sheet = $spreadsheet->getActiveSheet();
        $raw = $sheet->toArray();

        if (!is_array($raw) || count($raw) === 0) {
            return ['items' => [], 'errors' => ['Lembar Excel kosong.']];
        }

        $firstRow = array_shift($raw);
        if (!is_array($firstRow)) {
            return ['items' => [], 'errors' => ['Header Excel tidak valid.']];
        }

        $normalizedHeaders = array_map(
            fn ($cell) => $this->normalizeHeader((string) $cell),
            $firstRow
        );

        $matrix = [];
        $rowNumber = 1;
        foreach ($raw as $row) {
            $rowNumber++;
            if (!is_array($row)) {
                continue;
            }
            $assoc = [];
            foreach ($normalizedHeaders as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }
            if ($this->isRowEmpty($assoc)) {
                continue;
            }
            $matrix[] = ['row' => $rowNumber, 'data' => $assoc];
        }

        return $this->validateMatrixRows($matrix);
    }

    /**
     * @param  array<int, array{row: int, data: array<string, string>}>  $matrix
     * @return array{items: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    private function validateMatrixRows(array $matrix): array
    {
        $items = [];
        $errors = [];

        foreach ($matrix as $entry) {
            $rowNumber = $entry['row'];
            $assoc = $entry['data'];

            $namaBarang = $this->cellValue($assoc, ['nama_barang', 'barang', 'nama']);
            $jumlahRaw = $this->cellValue($assoc, ['jumlah', 'qty', 'kuantitas']);
            $satuan = $this->cellValue($assoc, ['satuan', 'unit']) ?: 'Unit';
            $distributionType = strtolower($this->cellValue($assoc, ['distribution_type', 'jenis_distribusi', 'jenis']) ?: 'offer');
            $noAktivaSap = $this->cellValue($assoc, ['no_aktiva_sap', 'no_aktiva', 'sap']);
            $kondisiBarang = strtolower($this->cellValue($assoc, ['kondisi_barang', 'kondisi']) ?: 'baik');
            $statusTps = $this->cellValue($assoc, ['status_tps', 'status_di_tps']) ?: 'Diperlukan';
            $tindakanBarang = $this->cellValue($assoc, ['tindakan_barang', 'tindakan']);

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

        return ['items' => $items, 'errors' => $errors];
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->lower()
            ->trim()
            ->replace(['.', '-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();
    }

    private function isRowEmpty(array $assoc): bool
    {
        foreach ($assoc as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function cellValue(array $assoc, array $aliases): string
    {
        foreach ($aliases as $key) {
            if (array_key_exists($key, $assoc)) {
                return trim((string) $assoc[$key]);
            }
        }

        return '';
    }
}
