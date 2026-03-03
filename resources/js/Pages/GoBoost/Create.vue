<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const user = usePage().props.auth.user;
const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
});

// Data statis untuk dropdown Bagian Kimia Farma
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

// Data untuk dropdown/radio Area Temuan
const areaOptions = [
    'Produksi',
    'Quality Control',
    'Maintenance',
    'HRD',
    'Finance',
    'IT',
    'Logistik',
    'Marketing',
    'R&D',
    'Administrasi',
    'Warehouse',
    'Security',
    'Parkir',
    'Kantin',
    'Area Umum',
];

// Computed property for current date and time
const currentDateTime = computed(() => {
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    return now.toLocaleDateString('id-ID', options);
});

const form = useForm({
    nama_karyawan: user.name || '',
    npp_karyawan: user.npp || '',
    bagian: '',
    area_temuan: '',
    ruangan_temuan: '',
    penjelasan_temuan: '',
    pic_terkait: '',
    mentioned_user_id: null,
    photo_temuan: [],
});

const photoPreviews = ref([]);
const maxFiles = 5;
const maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
const errorMessage = ref('');
const fileInputRef = ref(null);

const triggerCamera = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const handleFileChange = (event) => {
    errorMessage.value = '';
    const files = Array.from(event.target.files || []);
    
    // Validasi jumlah file
    const currentCount = form.photo_temuan.length;
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
            errorMessage.value = `File "${file.name}" melebihi 10MB. File diabaikan.`;
            continue;
        }
        validFiles.push(file);
    }
    
    // Tambahkan file valid ke form
    form.photo_temuan = [...form.photo_temuan, ...validFiles];
    
    // Generate preview untuk file baru
    validFiles.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreviews.value.push({
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
    const index = photoPreviews.value.findIndex((p) => p.id === previewId);
    if (index !== -1) {
        const preview = photoPreviews.value[index];
        // Hapus dari form
        form.photo_temuan = form.photo_temuan.filter((f) => f !== preview.file);
        // Hapus dari preview
        photoPreviews.value.splice(index, 1);
    }
};

const submit = () => {
    // Validasi final sebelum submit
    if (form.photo_temuan.length > maxFiles) {
        errorMessage.value = `Maksimal ${maxFiles} foto diperbolehkan.`;
        return;
    }
    
    form.post(route('go_boost.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            photoPreviews.value = [];
            errorMessage.value = '';
        },
    });
};
</script>

<template>
    <Head title="Form GO BOOST" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Form Input GO BOOST
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white/85 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="border-b border-white/60 px-8 py-6">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Data GO BOOST
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Lengkapi setiap bagian di bawah ini untuk memastikan temuan terdokumentasi sesuai standar GAMP 5.
                        </p>
                    </div>

                    <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-8 px-8 py-8">
                        <!-- Card 1: Identitas & Waktu (Wajib) -->
                        <section class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/40">
                            <div class="mb-6">
                                <h4 class="text-lg font-semibold text-gray-900">Identitas & Waktu</h4>
                                <p class="text-sm text-gray-500">Data personal, waktu, dan bagian otomatis terisi untuk keperluan traceability.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="nama_karyawan" value="Nama Karyawan" />
                                    <TextInput
                                        id="nama_karyawan"
                                        type="text"
                                        class="mt-2 block w-full bg-gray-50/60"
                                        v-model="form.nama_karyawan"
                                        readonly
                                    />
                                </div>

                                <div>
                                    <InputLabel for="npp_karyawan" value="NPP Karyawan" />
                                    <TextInput
                                        id="npp_karyawan"
                                        type="text"
                                        class="mt-2 block w-full bg-gray-50/60"
                                        v-model="form.npp_karyawan"
                                        readonly
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="current_datetime" value="Tanggal & Waktu" />
                                    <input
                                        id="current_datetime"
                                        type="text"
                                        :value="currentDateTime"
                                        readonly
                                        class="mt-2 block w-full rounded-xl border-0 bg-gray-50/90 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="bagian" value="Bagian *" />
                                    <select
                                        id="bagian"
                                        v-model="form.bagian"
                                        class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                        required
                                    >
                                        <option value="">-- Pilih Bagian --</option>
                                        <option
                                            v-for="bagian in departemenOptions"
                                            :key="bagian"
                                            :value="bagian"
                                        >
                                            {{ bagian }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.bagian" />
                                </div>
                            </div>
                        </section>

                        <!-- Bagian: Detail Lokasi -->
                        <section class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/40">
                            <div class="mb-6 flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">Detail Lokasi</h4>
                                    <p class="text-sm text-gray-500">Informasi tempat terjadinya temuan K3L.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="area_temuan" value="Area Terjadinya Temuan *" />
                                    <select
                                        id="area_temuan"
                                        v-model="form.area_temuan"
                                        class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                        required
                                    >
                                        <option value="">-- Pilih Area Temuan --</option>
                                        <option
                                            v-for="area in areaOptions"
                                            :key="area"
                                            :value="area"
                                        >
                                            {{ area }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.area_temuan" />
                                </div>

                                <div>
                                    <InputLabel for="ruangan_temuan" value="Ruangan/Tempat Temuan *" />
                                    <TextInput
                                        id="ruangan_temuan"
                                        type="text"
                                        class="mt-2 block w-full"
                                        v-model="form.ruangan_temuan"
                                        required
                                        placeholder="Contoh: Ruangan A-101, Hall Lantai 2, dll"
                                    />
                                    <InputError class="mt-2" :message="form.errors.ruangan_temuan" />
                                </div>
                            </div>
                        </section>

                        <!-- Bagian: Deskripsi Temuan -->
                        <section class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/40">
                            <div class="mb-6 flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">Deskripsi Temuan</h4>
                                    <p class="text-sm text-gray-500">Detail temuan untuk tindak lanjut tim terkait.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <InputLabel for="penjelasan_temuan" value="Penjelasan Singkat Temuan *" />
                                    <textarea
                                        id="penjelasan_temuan"
                                        v-model="form.penjelasan_temuan"
                                        rows="5"
                                        class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-3 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                        required
                                        placeholder="Jelaskan temuan ketidaksesuaian 5R yang ditemukan..."
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.penjelasan_temuan" />
                                </div>

                                <div>
                                    <InputLabel for="pic_terkait" value="Nama Lengkap PIC Terkait *" />
                                    <TextInput
                                        id="pic_terkait"
                                        type="text"
                                        class="mt-2 block w-full"
                                        v-model="form.pic_terkait"
                                        required
                                        placeholder="Masukkan nama PIC yang bertanggung jawab"
                                    />
                                    <InputError class="mt-2" :message="form.errors.pic_terkait" />
                                </div>

                                <div>
                                    <InputLabel for="mentioned_user_id" value="Mention User (Opsional)" />
                                    <select
                                        id="mentioned_user_id"
                                        v-model="form.mentioned_user_id"
                                        class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                    >
                                        <option :value="null">-- Pilih User untuk di-mention --</option>
                                        <option
                                            v-for="userOption in props.users"
                                            :key="userOption.id"
                                            :value="userOption.id"
                                        >
                                            {{ userOption.label }}
                                        </option>
                                    </select>
                                    <p class="mt-2 text-xs text-gray-500">
                                        Pilih user yang ingin Anda mention dalam GO BOOST ini. User yang di-mention akan menerima notifikasi.
                                    </p>
                                    <InputError class="mt-2" :message="form.errors.mentioned_user_id" />
                                </div>
                            </div>
                        </section>

                        <!-- Bagian: Dokumentasi -->
                        <section class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/40">
                            <div class="mb-6 flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">Dokumentasi</h4>
                                    <p class="text-sm text-gray-500">Unggah bukti visual untuk mempermudah analisa.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <InputLabel for="photo_temuan" value="Upload Foto Temuan" />
                                    
                                    <!-- Input File Tersembunyi -->
                                    <input
                                        id="photo_temuan"
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
                                        :disabled="photoPreviews.length >= maxFiles"
                                        class="mt-2 w-full inline-flex items-center justify-center gap-3 rounded-xl border-2 border-dashed border-[#00529b]/40 bg-gradient-to-br from-blue-50 via-white to-blue-50/60 px-6 py-4 text-sm font-semibold shadow-lg transition-all duration-200 hover:border-[#00529b]/60 focus:outline-none focus:ring-2 focus:ring-[#00529b] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:shadow-lg"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span>📷 Ambil Foto Langsung</span>
                                    </button>
                                    
                                    <p class="mt-3 text-xs font-medium" style="color: #00529b;">
                                        Maksimal 5 Foto @ 10MB per Foto
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Format: JPG, PNG, atau GIF. Klik tombol di atas untuk membuka kamera.
                                    </p>
                                    <div v-if="errorMessage" class="mt-2 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                                        {{ errorMessage }}
                                    </div>
                                    <div v-if="photoPreviews.length > 0" class="mt-2 text-xs text-gray-600">
                                        Foto terpilih: <span class="font-semibold">{{ photoPreviews.length }}/{{ maxFiles }}</span>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.photo_temuan" />
                                </div>

                                <div v-if="photoPreviews.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <div
                                        v-for="preview in photoPreviews"
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
                                            :alt="`Preview foto temuan ${photoPreviews.indexOf(preview) + 1}`"
                                            class="h-40 w-full rounded-lg object-cover"
                                        />
                                        <p class="mt-2 text-center text-xs font-medium text-gray-600">
                                            Foto {{ photoPreviews.indexOf(preview) + 1 }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Aksi -->
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
