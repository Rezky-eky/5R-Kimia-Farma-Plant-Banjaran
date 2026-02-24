<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const user = usePage().props.auth.user;

// List Bagian Kimia Farma
const departemenOptions = [
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

// Status di TPS
const statusTpsOptions = ['Diperlukan', 'Ragu-Ragu', 'Tidak Diperlukan'];

// Data untuk dropdown Satuan
const satuanOptions = [
    'Unit',
    'Pcs',
    'Box',
    'Kg',
    'Liter',
    'Meter',
    'Lembar',
    'Set',
    'Paket',
    'Lainnya'
];

// Data untuk dropdown Kondisi Barang
const kondisiOptions = [
    { value: 'baik', label: 'Baik' },
    { value: 'rusak', label: 'Rusak' },
    { value: 'kadaluarsa', label: 'Kadaluarsa' },
    { value: 'lainnya', label: 'Lainnya' }
];

// Format tanggal dan waktu saat ini
const currentDateTime = new Date().toLocaleString('id-ID', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
});

const form = useForm({
    nama_karyawan: user.name || '',
    npp_karyawan: user.npp || '',
    bagian: '',
    nama_ruangan: '',
    penjelasan_aksi: '',
    foto_kegiatan: [],
    latitude: null,
    longitude: null,
    list_barang_ringkas: [],
});

const fotoPreviews = ref([]);
const maxFiles = 5;
const maxFileSize = 2 * 1024 * 1024; // 2MB in bytes
const errorMessage = ref('');
const fileInputRef = ref(null);
const locationError = ref('');
const isGettingLocation = ref(false);

// Fungsi untuk mendapatkan lokasi GPS
const getLocation = () => {
    if (!navigator.geolocation) {
        locationError.value = 'Geolocation tidak didukung oleh browser Anda.';
        return;
    }

    isGettingLocation.value = true;
    locationError.value = '';

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude;
            form.longitude = position.coords.longitude;
            isGettingLocation.value = false;
            locationError.value = '';
        },
        (error) => {
            isGettingLocation.value = false;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    locationError.value = 'Izin lokasi ditolak. Silakan aktifkan izin lokasi di pengaturan browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    locationError.value = 'Informasi lokasi tidak tersedia.';
                    break;
                case error.TIMEOUT:
                    locationError.value = 'Waktu permintaan lokasi habis.';
                    break;
                default:
                    locationError.value = 'Terjadi kesalahan saat mengambil lokasi.';
                    break;
            }
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
};

// Ambil lokasi otomatis saat komponen dimuat
onMounted(() => {
    getLocation();
});

const triggerCamera = () => {
    // Ambil lokasi saat tombol kamera ditekan (jika belum ada)
    if (!form.latitude || !form.longitude) {
        getLocation();
    }
    
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const handleFileChange = (event) => {
    errorMessage.value = '';
    const files = Array.from(event.target.files || []);
    
    // Validasi jumlah file
    const currentCount = form.foto_kegiatan.length;
    const newFilesCount = files.length;
    const totalCount = currentCount + newFilesCount;
    
    if (totalCount > maxFiles) {
        errorMessage.value = `Maksimal ${maxFiles} foto. Anda telah memilih ${totalCount} foto.`;
        event.target.value = '';
        return;
    }
    
    // Validasi ukuran file dan filter
    const validFiles = [];
    for (const file of files) {
        if (file.size > maxFileSize) {
            errorMessage.value = `File "${file.name}" melebihi 2MB. File diabaikan.`;
            continue;
        }
        validFiles.push(file);
    }
    
    // Tambahkan file valid ke form
    form.foto_kegiatan = [...form.foto_kegiatan, ...validFiles];
    
    // Generate preview untuk file baru
    validFiles.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            fotoPreviews.value.push({
                id: Date.now() + Math.random(),
                url: e.target.result,
                file: file,
            });
        };
        reader.readAsDataURL(file);
    });
    
    // Reset input untuk memungkinkan memilih file yang sama lagi
    event.target.value = '';
};

const removePhoto = (previewId) => {
    const index = fotoPreviews.value.findIndex((p) => p.id === previewId);
    if (index !== -1) {
        const preview = fotoPreviews.value[index];
        // Hapus dari form
        form.foto_kegiatan = form.foto_kegiatan.filter((f) => f !== preview.file);
        // Hapus dari preview
        fotoPreviews.value.splice(index, 1);
    }
};

// Fungsi untuk menambahkan item DBR baru
const addBarang = () => {
    form.list_barang_ringkas.push({
        nama_barang: '',
        jumlah: 1,
        satuan: 'Unit',
        no_aktiva_sap: '',
        kondisi_barang: 'baik',
        status_tps: 'Diperlukan',
        tindakan_barang: '',
    });
};

// Fungsi untuk menghapus item DBR
const removeBarang = (index) => {
    form.list_barang_ringkas.splice(index, 1);
};

const submit = () => {
    // Validasi final sebelum submit
    if (form.foto_kegiatan.length > maxFiles) {
        errorMessage.value = `Maksimal ${maxFiles} foto diperbolehkan.`;
        return;
    }

    if (!form.latitude || !form.longitude) {
        locationError.value = 'Lokasi GPS wajib diambil. Silakan refresh halaman atau aktifkan izin lokasi.';
        return;
    }

    // Validasi fleksibilitas: minimal salah satu (Foto/Aksi ATAU DBR)
    const hasFotoAksi = form.foto_kegiatan.length > 0 || form.penjelasan_aksi.trim() !== '';
    const hasDBR = form.list_barang_ringkas.length > 0;

    if (!hasFotoAksi && !hasDBR) {
        errorMessage.value = 'Minimal salah satu harus diisi: Foto/Aksi ATAU Daftar Barang Ringkas.';
        return;
    }
    
    form.post(route('go_action.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            form.list_barang_ringkas = [];
            fotoPreviews.value = [];
            errorMessage.value = '';
            locationError.value = '';
            // Ambil lokasi lagi setelah reset
            getLocation();
        },
    });
};
</script>

<template>
    <Head title="Form GO ACTION" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Form Input GO ACTION
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white/85 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="border-b border-white/60 px-8 py-6">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Data GO ACTION
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Catat setiap aksi ringkas secara terstruktur agar mudah diverifikasi dan diaudit.
                        </p>
                    </div>

                    <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-8 px-8 py-8">
                        <!-- Card 1: Identitas & Lokasi (Wajib) -->
                        <section class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/40">
                            <div class="mb-6">
                                <h4 class="text-lg font-semibold text-gray-900">Identitas & Lokasi</h4>
                                <p class="text-sm text-gray-500">Data identitas dan lokasi wajib diisi untuk keperluan traceability.</p>
                            </div>

                            <!-- Identitas Karyawan & Waktu -->
                            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-4">
                            <div>
                                <InputLabel for="nama_karyawan" value="Nama Karyawan" />
                                <TextInput
                                    id="nama_karyawan"
                                    type="text"
                                        class="mt-2 block w-full bg-gray-50/60"
                                    v-model="form.nama_karyawan"
                                    readonly
                                />
                                <InputError class="mt-2" :message="form.errors.nama_karyawan" />
                            </div>

                            <div>
                                    <InputLabel for="npp_karyawan" value="NIK (NPP)" />
                                <TextInput
                                    id="npp_karyawan"
                                    type="text"
                                        class="mt-2 block w-full bg-gray-50/60"
                                    v-model="form.npp_karyawan"
                                    readonly
                                />
                                <InputError class="mt-2" :message="form.errors.npp_karyawan" />
                            </div>

                                <div>
                                    <InputLabel for="tanggal_waktu" value="Tanggal/Waktu" />
                                    <input
                                        id="tanggal_waktu"
                                        type="text"
                                        :value="currentDateTime"
                                        readonly
                                        class="mt-2 block w-full rounded-xl border-0 bg-gray-50/90 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                    />
                                </div>

                            </div>

                            <!-- Lokasi -->
                            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                    <InputLabel for="bagian" value="Bagian *" />
                                <select
                                    id="bagian"
                                    v-model="form.bagian"
                                        class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                    required
                                >
                                    <option value="">-- Pilih Bagian --</option>
                                    <option
                                            v-for="departemen in departemenOptions"
                                            :key="departemen"
                                            :value="departemen"
                                        >
                                            {{ departemen }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.bagian" />
                            </div>

                                <div>
                                    <InputLabel for="nama_ruangan" value="Nama Ruangan" />
                                    <TextInput
                                        id="nama_ruangan"
                                        type="text"
                                        class="mt-2 block w-full"
                                        v-model="form.nama_ruangan"
                                        placeholder="Contoh: Ruangan A-101"
                                    />
                                    <InputError class="mt-2" :message="form.errors.nama_ruangan" />
                                </div>
                            </div>

                            <!-- Konfirmasi Geotagging -->
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h5 class="text-sm font-semibold text-gray-700">Konfirmasi Geotagging</h5>
                                    <p class="text-xs text-gray-500">Koordinat lokasi diambil otomatis untuk keperluan traceability.</p>
                                </div>
                                <button
                                    type="button"
                                    @click="getLocation"
                                    :disabled="isGettingLocation"
                                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-[#00529b] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        style="background-color: #00529b; box-shadow: 0 10px 15px -3px rgba(0, 82, 155, 0.3);"
                                >
                                    <svg v-if="isGettingLocation" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>{{ isGettingLocation ? 'Mengambil...' : 'Refresh Lokasi' }}</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="latitude" value="Latitude" />
                                    <TextInput
                                        id="latitude"
                                        type="text"
                                        class="mt-2 block w-full bg-gray-50/60"
                                        v-model="form.latitude"
                                        readonly
                                    />
                                    <InputError class="mt-2" :message="form.errors.latitude" />
                                </div>

                                <div>
                                    <InputLabel for="longitude" value="Longitude" />
                                    <TextInput
                                        id="longitude"
                                        type="text"
                                        class="mt-2 block w-full bg-gray-50/60"
                                        v-model="form.longitude"
                                        readonly
                                    />
                                    <InputError class="mt-2" :message="form.errors.longitude" />
                                </div>
                            </div>

                            <div v-if="locationError" class="mt-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                                {{ locationError }}
                            </div>
                        </section>

                        <!-- Card 2: Foto & Detail Aksi (Opsional) -->
                        <section class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/40">
                            <div class="mb-6 flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">Foto & Detail Aksi</h4>
                                    <p class="text-sm text-gray-500">Opsional: Deskripsi aksi dan foto kegiatan.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                            <div>
                                    <InputLabel for="penjelasan_aksi" value="Deskripsi Detail Aksi yang Dilakukan" />
                                <textarea
                                    id="penjelasan_aksi"
                                    v-model="form.penjelasan_aksi"
                                    rows="5"
                                        class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-3 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                        placeholder="Masukkan deskripsi detail aksi yang telah dilakukan (opsional)..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.penjelasan_aksi" />
                            </div>

                                <!-- Foto Kegiatan -->
                                <div>
                                    <InputLabel for="foto_kegiatan" value="Foto Kegiatan" />
                                    
                                    <!-- Input File Tersembunyi -->
                                    <input
                                        id="foto_kegiatan"
                                        ref="fileInputRef"
                                        type="file"
                                        accept="image/*"
                                        capture="environment"
                                        multiple
                                        @change="handleFileChange"
                                        class="hidden"
                                    />
                                    
                                    <!-- Tombol Pemicu Kamera -->
                                    <button
                                        type="button"
                                        @click="triggerCamera"
                                        :disabled="fotoPreviews.length >= maxFiles"
                                        class="mt-2 w-full inline-flex items-center justify-center gap-3 rounded-xl border-2 border-dashed border-[#00529b]/40 bg-gradient-to-br from-blue-50 via-white to-blue-50/60 px-6 py-4 text-sm font-semibold shadow-lg transition-all duration-200 hover:border-[#00529b]/60 focus:outline-none focus:ring-2 focus:ring-[#00529b] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:shadow-lg"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span>📷 Ambil Foto Langsung</span>
                                    </button>
                                    
                                    <p class="mt-3 text-xs font-medium" style="color: #00529b;">
                                        Maksimal 5 Foto @ 2MB per Foto
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Format: JPG, PNG, atau GIF. Klik tombol di atas untuk membuka kamera.
                                    </p>
                                    <div v-if="errorMessage" class="mt-2 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                                        {{ errorMessage }}
                                    </div>
                                    <div v-if="fotoPreviews.length > 0" class="mt-2 text-xs text-gray-600">
                                        Foto terpilih: <span class="font-semibold">{{ fotoPreviews.length }}/{{ maxFiles }}</span>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.foto_kegiatan" />
                                </div>

                                <div v-if="fotoPreviews.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <div
                                        v-for="preview in fotoPreviews"
                                        :key="preview.id"
                                        class="group relative rounded-xl border border-blue-100 bg-blue-50/50 p-3 shadow-lg"
                                    >
                                        <button
                                            type="button"
                                            @click="removePhoto(preview.id)"
                                            class="absolute -right-2 -top-2 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow-md transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2"
                                            aria-label="Hapus foto"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                        <img
                                            :src="preview.url"
                                            :alt="`Preview foto kegiatan ${fotoPreviews.indexOf(preview) + 1}`"
                                            class="h-40 w-full rounded-lg object-cover"
                                        />
                                        <p class="mt-2 text-center text-xs font-medium text-gray-600">
                                            Foto {{ fotoPreviews.indexOf(preview) + 1 }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Card 3: Daftar Barang Ringkas (DBR) (Opsional - Dinamis) -->
                        <section class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/40">
                            <div class="mb-6 flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">Daftar Barang Ringkas (DBR)</h4>
                                    <p class="text-sm text-gray-500">Opsional: Catat setiap barang yang ditemukan dalam aksi ringkas ini.</p>
                                </div>
                                <button
                                    type="button"
                                    @click="addBarang"
                                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-[#00529b] focus:ring-offset-2"
                                        style="background-color: #00529b; box-shadow: 0 10px 15px -3px rgba(0, 82, 155, 0.3);"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Tambah Barang</span>
                                </button>
                            </div>

                            <div v-if="form.list_barang_ringkas.length === 0" class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-6 text-center text-sm text-gray-500">
                                Belum ada barang yang dicatat. Klik tombol "Tambah Barang" untuk menambahkan.
                            </div>

                            <div v-else class="space-y-4">
                                <div
                                    v-for="(barang, index) in form.list_barang_ringkas"
                                    :key="index"
                                    class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 shadow-inner"
                                >
                                    <div class="mb-4 flex items-center justify-between">
                                        <h5 class="text-sm font-semibold text-gray-700">Barang #{{ index + 1 }}</h5>
                                        <button
                                            type="button"
                                            @click="removeBarang(index)"
                                            class="inline-flex items-center justify-center rounded-lg bg-red-500 px-3 py-1.5 text-xs font-semibold text-white shadow-md transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            <span class="ml-1">Hapus</span>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                        <div class="md:col-span-2">
                                            <InputLabel :for="`nama_barang_${index}`" value="Nama Barang *" />
                                            <TextInput
                                                :id="`nama_barang_${index}`"
                                                type="text"
                                                class="mt-2 block w-full"
                                                v-model="barang.nama_barang"
                                                required
                                                placeholder="Masukkan nama barang..."
                                            />
                                            <InputError class="mt-2" :message="form.errors[`list_barang_ringkas.${index}.nama_barang`]" />
                                        </div>

                                        <div>
                                            <InputLabel :for="`jumlah_${index}`" value="Jumlah *" />
                                            <TextInput
                                                :id="`jumlah_${index}`"
                                                type="number"
                                                min="1"
                                                class="mt-2 block w-full"
                                                v-model.number="barang.jumlah"
                                                required
                                            />
                                            <InputError class="mt-2" :message="form.errors[`list_barang_ringkas.${index}.jumlah`]" />
                                        </div>

                                        <div>
                                            <InputLabel :for="`satuan_${index}`" value="Satuan *" />
                                            <select
                                                :id="`satuan_${index}`"
                                                v-model="barang.satuan"
                                                class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                                required
                                            >
                                                <option
                                                    v-for="satuan in satuanOptions"
                                                    :key="satuan"
                                                    :value="satuan"
                                                >
                                                    {{ satuan }}
                                                </option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors[`list_barang_ringkas.${index}.satuan`]" />
                                        </div>

                                        <div>
                                            <InputLabel :for="`no_aktiva_sap_${index}`" value="No Aktiva/SAP" />
                                            <TextInput
                                                :id="`no_aktiva_sap_${index}`"
                                                type="text"
                                                class="mt-2 block w-full"
                                                v-model="barang.no_aktiva_sap"
                                                placeholder="Opsional"
                                            />
                                            <InputError class="mt-2" :message="form.errors[`list_barang_ringkas.${index}.no_aktiva_sap`]" />
                                        </div>

                                        <div>
                                            <InputLabel :for="`kondisi_barang_${index}`" value="Kondisi Barang *" />
                                            <select
                                                :id="`kondisi_barang_${index}`"
                                                v-model="barang.kondisi_barang"
                                                class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                                required
                                            >
                                                <option
                                                    v-for="kondisi in kondisiOptions"
                                                    :key="kondisi.value"
                                                    :value="kondisi.value"
                                                >
                                                    {{ kondisi.label }}
                                                </option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors[`list_barang_ringkas.${index}.kondisi_barang`]" />
                                        </div>

                                        <div>
                                            <InputLabel :for="`status_tps_${index}`" value="Status di TPS *" />
                                            <select
                                                :id="`status_tps_${index}`"
                                                v-model="barang.status_tps"
                                                class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                                required
                                            >
                                                <option
                                                    v-for="status in statusTpsOptions"
                                                    :key="status"
                                                    :value="status"
                                                >
                                                    {{ status }}
                                                </option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors[`list_barang_ringkas.${index}.status_tps`]" />
                                        </div>

                                        <div class="md:col-span-2">
                                            <InputLabel :for="`tindakan_barang_${index}`" value="Tindakan terhadap Barang" />
                                            <TextInput
                                                :id="`tindakan_barang_${index}`"
                                                type="text"
                                                class="mt-2 block w-full"
                                                v-model="barang.tindakan_barang"
                                                placeholder="Masukkan tindakan yang dilakukan terhadap barang (opsional)..."
                                            />
                                            <InputError class="mt-2" :message="form.errors[`list_barang_ringkas.${index}.tindakan_barang`]" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <InputError class="mt-4" :message="form.errors.list_barang_ringkas" />
                        </section>

                        <div class="flex flex-col gap-4 border-t border-blue-100/60 pt-6 sm:flex-row sm:items-center sm:justify-end">
                            <Link
                                :href="route('dashboard')"
                                class="inline-flex items-center justify-center rounded-xl bg-gray-200 px-5 py-2 text-sm font-semibold text-gray-700 shadow-md shadow-gray-300/60 transition hover:bg-gray-300 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                            >
                                Batalkan
                            </Link>

                            <PrimaryButton :disabled="form.processing" :aria-busy="form.processing">
                                    <span v-if="form.processing">Menyimpan...</span>
                                    <span v-else>Simpan Data</span>
                                </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

