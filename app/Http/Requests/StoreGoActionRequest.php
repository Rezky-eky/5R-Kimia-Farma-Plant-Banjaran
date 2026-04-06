<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;

class StoreGoActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // npp_karyawan dan nama_karyawan tidak perlu divalidasi karena diambil langsung dari user yang login
            'bagian' => ['required', 'string', 'max:255'],
            'nama_ruangan' => ['nullable', 'string', 'max:255'],
            'kode_ruangan' => ['nullable', 'string', 'max:100'],
            'penjelasan_aksi' => ['nullable', 'string'],
            'foto_kegiatan' => ['nullable', 'array', 'max:5'],
            'foto_kegiatan.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'list_barang_ringkas' => ['nullable', 'array'],
            'list_barang_ringkas.*.nama_barang' => ['required_with:list_barang_ringkas', 'string', 'max:255'],
            'list_barang_ringkas.*.jumlah' => ['required_with:list_barang_ringkas', 'integer', 'min:1'],
            'list_barang_ringkas.*.satuan' => ['required_with:list_barang_ringkas', 'string', 'max:50'],
            'list_barang_ringkas.*.distribution_type' => ['required_with:list_barang_ringkas', Rule::in(['offer', 'sale'])],
            'list_barang_ringkas.*.no_aktiva_sap' => ['nullable', 'string', 'max:100'],
            'list_barang_ringkas.*.kondisi_barang' => ['required_with:list_barang_ringkas', 'in:baik,rusak,kadaluarsa,lainnya'],
            'list_barang_ringkas.*.status_tps' => ['required_with:list_barang_ringkas', Rule::in(['Diperlukan', 'Ragu-Ragu', 'Tidak Diperlukan'])],
            'list_barang_ringkas.*.tindakan_barang' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $hasFotoAksi = $this->hasFile('foto_kegiatan') && count($this->file('foto_kegiatan', [])) > 0;
            $hasPenjelasan = !empty($this->penjelasan_aksi);
            $hasDBR = !empty($this->list_barang_ringkas) && is_array($this->list_barang_ringkas) && count($this->list_barang_ringkas) > 0;

            // Validasi: Minimal salah satu harus diisi (Foto/Aksi ATAU DBR)
            if (!$hasFotoAksi && !$hasPenjelasan && !$hasDBR) {
                $validator->errors()->add(
                    'foto_kegiatan',
                    'Minimal salah satu harus diisi: Foto/Aksi ATAU Daftar Barang Ringkas.'
                );
            }

            // Jika ada foto, validasi lebih ketat
            if ($hasFotoAksi && count($this->file('foto_kegiatan', [])) > 5) {
                $validator->errors()->add(
                    'foto_kegiatan',
                    'Maksimal 5 foto yang dapat diunggah.'
                );
            }

            // Jika ada DBR, validasi setiap item
            if ($hasDBR) {
                foreach ($this->list_barang_ringkas as $index => $barang) {
                    if (empty($barang['nama_barang'])) {
                        $validator->errors()->add(
                            "list_barang_ringkas.{$index}.nama_barang",
                            'Nama barang wajib diisi.'
                        );
                    }
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bagian.required' => 'Bagian wajib dipilih.',
            'nama_ruangan.string' => 'Nama ruangan harus berupa teks.',
            'nama_ruangan.max' => 'Nama ruangan maksimal 255 karakter.',
            'kode_ruangan.string' => 'Kode ruangan harus berupa teks.',
            'kode_ruangan.max' => 'Kode ruangan maksimal 100 karakter.',
            'penjelasan_aksi.string' => 'Penjelasan aksi harus berupa teks.',
            'foto_kegiatan.array' => 'Foto Kegiatan harus berupa array.',
            'foto_kegiatan.max' => 'Maksimal 5 foto yang dapat diunggah.',
            'foto_kegiatan.*.image' => 'Setiap foto harus berupa file gambar.',
            'foto_kegiatan.*.mimes' => 'Format foto harus JPG, PNG, atau GIF.',
            'foto_kegiatan.*.max' => 'Ukuran setiap foto maksimal 10MB.',
            'latitude.required' => 'Lokasi GPS wajib diambil. Pastikan izin lokasi diaktifkan.',
            'latitude.numeric' => 'Koordinat latitude tidak valid.',
            'longitude.required' => 'Lokasi GPS wajib diambil. Pastikan izin lokasi diaktifkan.',
            'longitude.numeric' => 'Koordinat longitude tidak valid.',
            'list_barang_ringkas.array' => 'Daftar Barang Ringkas harus berupa array.',
            'list_barang_ringkas.*.nama_barang.required_with' => 'Nama barang wajib diisi.',
            'list_barang_ringkas.*.nama_barang.string' => 'Nama barang harus berupa teks.',
            'list_barang_ringkas.*.nama_barang.max' => 'Nama barang maksimal 255 karakter.',
            'list_barang_ringkas.*.jumlah.required_with' => 'Jumlah barang wajib diisi.',
            'list_barang_ringkas.*.jumlah.integer' => 'Jumlah barang harus berupa angka.',
            'list_barang_ringkas.*.jumlah.min' => 'Jumlah barang minimal 1.',
            'list_barang_ringkas.*.satuan.required_with' => 'Satuan wajib diisi.',
            'list_barang_ringkas.*.satuan.string' => 'Satuan harus berupa teks.',
            'list_barang_ringkas.*.satuan.max' => 'Satuan maksimal 50 karakter.',
            'list_barang_ringkas.*.no_aktiva_sap.string' => 'No Aktiva/SAP harus berupa teks.',
            'list_barang_ringkas.*.no_aktiva_sap.max' => 'No Aktiva/SAP maksimal 100 karakter.',
            'list_barang_ringkas.*.kondisi_barang.required_with' => 'Kondisi barang wajib dipilih.',
            'list_barang_ringkas.*.kondisi_barang.in' => 'Kondisi barang tidak valid.',
            'list_barang_ringkas.*.status_tps.required_with' => 'Status di TPS wajib dipilih.',
            'list_barang_ringkas.*.status_tps.in' => 'Status di TPS tidak valid.',
            'list_barang_ringkas.*.tindakan_barang.string' => 'Tindakan terhadap barang harus berupa teks.',
            'list_barang_ringkas.*.tindakan_barang.max' => 'Tindakan terhadap barang maksimal 500 karakter.',
        ];
    }
}

