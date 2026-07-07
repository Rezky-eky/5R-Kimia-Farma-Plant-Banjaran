<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PhotoImagePicker from '@/Components/PhotoImagePicker.vue';

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

const submit = () => {
    form.post(route('go_boost.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
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
                                    <TextInput
                                        id="area_temuan"
                                        type="text"
                                        class="mt-2 block w-full"
                                        v-model="form.area_temuan"
                                        required
                                        placeholder="Contoh: Area Produksi, Gudang A, Parkir, dll"
                                    />
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
                                        v-model.number="form.mentioned_user_id"
                                        class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                    >
                                        <option :value="null">-- Pilih User untuk di-mention --</option>
                                        <option
                                            v-for="userOption in props.users"
                                            :key="userOption.id"
                                            :value="userOption.id"
                                        >
                                            {{ userOption.label || `${userOption.name} (${userOption.npp})` }}
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
                                    <PhotoImagePicker
                                        v-model="form.photo_temuan"
                                        input-id="go-boost-photo-temuan"
                                        label=""
                                        hint="Maksimal 5 foto @ 10MB. Ambil foto atau pilih dari galeri."
                                    >
                                        <InputError class="mt-2" :message="form.errors.photo_temuan" />
                                    </PhotoImagePicker>
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
