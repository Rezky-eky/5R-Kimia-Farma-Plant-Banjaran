<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const user = usePage().props.auth.user;

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
    bagian_temuan: '',
    area_temuan: '',
    penjelasan_temuan: '',
    photo_before: [],
    penjelasan_capa: '',
    photo_after: [],
});

const photoBeforePreviews = ref([]);
const photoAfterPreviews = ref([]);
const maxFiles = 5;
const maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
const errorMessageBefore = ref('');
const errorMessageAfter = ref('');
const fileInputBeforeRef = ref(null);
const fileInputAfterRef = ref(null);

const triggerCamera = (type) => {
    const inputRef = type === 'before' ? fileInputBeforeRef : fileInputAfterRef;
    if (inputRef.value) {
        inputRef.value.click();
    }
};

const handleFileChange = (event, type) => {
    const errorRef = type === 'before' ? errorMessageBefore : errorMessageAfter;
    const previewsRef = type === 'before' ? photoBeforePreviews : photoAfterPreviews;
    const formField = type === 'before' ? form.photo_before : form.photo_after;
    
    errorRef.value = '';
    const files = Array.from(event.target.files || []);
    
    // Validasi jumlah file
    const currentCount = formField.length;
    const newFilesCount = files.length;
    const totalCount = currentCount + newFilesCount;
    
    if (totalCount > maxFiles) {
        errorRef.value = `Maksimal ${maxFiles} foto. Anda telah memilih ${totalCount} foto.`;
        event.target.value = '';
        return;
    }
    
    // Validasi ukuran file dan filter
    const validFiles = [];
    for (const file of files) {
        if (file.size > maxFileSize) {
            errorRef.value = `File "${file.name}" melebihi 10MB. File diabaikan.`;
            continue;
        }
        validFiles.push(file);
    }
    
    // Tambahkan file valid ke form
    if (type === 'before') {
        form.photo_before = [...form.photo_before, ...validFiles];
    } else {
        form.photo_after = [...form.photo_after, ...validFiles];
    }
    
    // Generate preview untuk file baru
    validFiles.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewsRef.value.push({
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

const removePhoto = (previewId, type) => {
    const previewsRef = type === 'before' ? photoBeforePreviews : photoAfterPreviews;
    const formField = type === 'before' ? form.photo_before : form.photo_after;
    
    const index = previewsRef.value.findIndex((p) => p.id === previewId);
    if (index !== -1) {
        const preview = previewsRef.value[index];
        // Hapus dari form
        if (type === 'before') {
            form.photo_before = form.photo_before.filter((f) => f !== preview.file);
        } else {
            form.photo_after = form.photo_after.filter((f) => f !== preview.file);
        }
        // Hapus dari preview
        previewsRef.value.splice(index, 1);
    }
};

const submit = () => {
    // Validasi final sebelum submit
    if (form.photo_before.length > maxFiles) {
        errorMessageBefore.value = `Maksimal ${maxFiles} foto diperbolehkan.`;
        return;
    }
    if (form.photo_after.length > maxFiles) {
        errorMessageAfter.value = `Maksimal ${maxFiles} foto diperbolehkan.`;
        return;
    }

    if (form.photo_before.length === 0) {
        errorMessageBefore.value = 'Foto before wajib diupload minimal 1.';
        return;
    }
    if (form.photo_after.length === 0) {
        errorMessageAfter.value = 'Foto after wajib diupload minimal 1.';
        return;
    }
    
    form.post(route('go_care.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            photoBeforePreviews.value = [];
            photoAfterPreviews.value = [];
            errorMessageBefore.value = '';
            errorMessageAfter.value = '';
        },
    });
};
</script>

<template>
    <Head title="Form GO CARE" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Form Input GO CARE
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white/85 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="border-b border-white/60 px-8 py-6">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Data GO CARE
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Silakan lengkapi data perbaikan untuk memastikan proses CAPA terdokumentasi secara profesional.
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

                        <div class="space-y-8">
                            <section class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/40">
                                <div class="mb-6">
                                    <h4 class="text-lg font-semibold text-gray-900">Data Temuan</h4>
                                    <p class="text-sm text-gray-500">Identifikasi temuan sebelum tindakan perbaikan dilakukan.</p>
                                </div>

                                <div class="space-y-6">
                                    <div>
                                        <InputLabel for="bagian_temuan" value="Bagian Tempat Terjadinya Temuan *" />
                                        <select
                                            id="bagian_temuan"
                                            v-model="form.bagian_temuan"
                                            class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                            required
                                        >
                                            <option value="">-- Pilih Bagian Temuan --</option>
                                            <option
                                                v-for="bagian in departemenOptions"
                                                :key="bagian"
                                                :value="bagian"
                                            >
                                                {{ bagian }}
                                            </option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.bagian_temuan" />
                                    </div>

                                    <div>
                                        <InputLabel for="area_temuan" value="Area Temuan" />
                                        <input
                                            id="area_temuan"
                                            v-model="form.area_temuan"
                                            type="text"
                                            class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                            placeholder="Contoh: Area Produksi, Gudang A, dll."
                                        />
                                        <InputError class="mt-2" :message="form.errors.area_temuan" />
                                    </div>

                                    <div>
                                        <InputLabel for="penjelasan_temuan" value="Penjelasan Temuan *" />
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
                                        <InputLabel for="photo_before" value="Upload Foto Temuan (Sebelum Perbaikan)" />
                                        
                                        <!-- Input File Tersembunyi -->
                                        <input
                                            id="photo_before"
                                            ref="fileInputBeforeRef"
                                            type="file"
                                            accept="image/*"
                                            capture="environment"
                                            multiple
                                            @change="(e) => handleFileChange(e, 'before')"
                                            class="hidden"
                                        />
                                        
                                        <!-- Tombol Pemicu Kamera -->
                                        <button
                                            type="button"
                                            @click="triggerCamera('before')"
                                            :disabled="photoBeforePreviews.length >= maxFiles"
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
                                        <div v-if="errorMessageBefore" class="mt-2 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                                            {{ errorMessageBefore }}
                                        </div>
                                        <div v-if="photoBeforePreviews.length > 0" class="mt-2 text-xs text-gray-600">
                                            Foto terpilih: <span class="font-semibold">{{ photoBeforePreviews.length }}/{{ maxFiles }}</span>
                                        </div>
                                        <InputError class="mt-2" :message="form.errors.photo_before" />

                                        <div v-if="photoBeforePreviews.length > 0" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div
                                                v-for="preview in photoBeforePreviews"
                                                :key="preview.id"
                                                class="group relative rounded-xl border border-blue-100 bg-blue-50/50 p-3 shadow-lg"
                                            >
                                                <button
                                                    type="button"
                                                    @click="removePhoto(preview.id, 'before')"
                                                    class="absolute -right-2 -top-2 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow-md transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2"
                                                    aria-label="Hapus foto"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                                <img
                                                    :src="preview.url"
                                                    :alt="`Preview foto sebelum perbaikan ${photoBeforePreviews.indexOf(preview) + 1}`"
                                                    class="h-40 w-full rounded-lg object-cover"
                                                />
                                                <p class="mt-2 text-center text-xs font-medium text-gray-600">
                                                    Foto {{ photoBeforePreviews.indexOf(preview) + 1 }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-2xl bg-white/95 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/40">
                                <div class="mb-6">
                                    <h4 class="text-lg font-semibold text-gray-900">Data Perbaikan</h4>
                                    <p class="text-sm text-gray-500">Dokumentasikan tindakan perbaikan dan bukti visualnya.</p>
                                </div>

                                <div class="space-y-6">
                                    <div>
                                        <InputLabel for="penjelasan_capa" value="Penjelasan Tindakan Perbaikan (CAPA) *" />
                                        <textarea
                                            id="penjelasan_capa"
                                            v-model="form.penjelasan_capa"
                                            rows="5"
                                            class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-3 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                            required
                                            placeholder="Jelaskan tindakan perbaikan yang telah dilakukan..."
                                        ></textarea>
                                        <InputError class="mt-2" :message="form.errors.penjelasan_capa" />
                                    </div>

                                    <div>
                                        <InputLabel for="photo_after" value="Upload Foto Temuan (Setelah Perbaikan)" />
                                        
                                        <!-- Input File Tersembunyi -->
                                        <input
                                            id="photo_after"
                                            ref="fileInputAfterRef"
                                            type="file"
                                            accept="image/*"
                                            capture="environment"
                                            multiple
                                            @change="(e) => handleFileChange(e, 'after')"
                                            class="hidden"
                                        />
                                        
                                        <!-- Tombol Pemicu Kamera -->
                                        <button
                                            type="button"
                                            @click="triggerCamera('after')"
                                            :disabled="photoAfterPreviews.length >= maxFiles"
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
                                        <div v-if="errorMessageAfter" class="mt-2 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                                            {{ errorMessageAfter }}
                                        </div>
                                        <div v-if="photoAfterPreviews.length > 0" class="mt-2 text-xs text-gray-600">
                                            Foto terpilih: <span class="font-semibold">{{ photoAfterPreviews.length }}/{{ maxFiles }}</span>
                                        </div>
                                        <InputError class="mt-2" :message="form.errors.photo_after" />

                                        <div v-if="photoAfterPreviews.length > 0" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div
                                                v-for="preview in photoAfterPreviews"
                                                :key="preview.id"
                                                class="group relative rounded-xl border border-blue-100 bg-blue-50/50 p-3 shadow-lg"
                                            >
                                                <button
                                                    type="button"
                                                    @click="removePhoto(preview.id, 'after')"
                                                    class="absolute -right-2 -top-2 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow-md transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2"
                                                    aria-label="Hapus foto"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                                <img
                                                    :src="preview.url"
                                                    :alt="`Preview foto setelah perbaikan ${photoAfterPreviews.indexOf(preview) + 1}`"
                                                    class="h-40 w-full rounded-lg object-cover"
                                                />
                                                <p class="mt-2 text-center text-xs font-medium text-gray-600">
                                                    Foto {{ photoAfterPreviews.indexOf(preview) + 1 }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
