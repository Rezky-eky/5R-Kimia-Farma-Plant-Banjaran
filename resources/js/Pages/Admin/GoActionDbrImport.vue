<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    departemenOptions: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    user_id: '',
    bagian: '',
    nama_ruangan: '',
    penjelasan_aksi: '',
    latitude: '-6.950000',
    longitude: '107.570000',
    excel_file: null,
});

const submit = () => {
    form.post(route('admin.go_action.dbr_import_store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};

const onFile = (e) => {
    const f = e.target.files?.[0];
    form.excel_file = f || null;
};
</script>

<template>
    <Head title="Impor DBR (Excel) - Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Impor Daftar Barang Ringkas (Excel)
                </h2>
                <div class="flex flex-wrap gap-2">
                    <a
                        :href="route('admin.go_action.dbr_template')"
                        class="inline-flex items-center rounded-xl bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-300"
                    >
                        Unduh template Excel
                    </a>
                    <a
                        :href="route('admin.go_action.dbr_export')"
                        class="inline-flex items-center rounded-xl bg-[#00529b]/10 px-4 py-2 text-sm font-semibold text-[#00529b] hover:bg-[#00529b]/20"
                    >
                        Ekspor semua DBR ke Excel
                    </a>
                    <Link
                        :href="route('admin.go_action.index')"
                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Data GO ACTION
                    </Link>
                    <BackToDashboard admin />
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-100">
                    <p class="text-sm text-gray-600 mb-6">
                        Upload file <strong>.xlsx</strong> (Excel) dengan header kolom seperti template. Hanya admin yang dapat mengisi DBR lewat Excel.
                        Data akan disimpan sebagai satu laporan GO ACTION untuk user yang Anda pilih.
                    </p>

                    <form class="space-y-6" @submit.prevent="submit">
                        <div>
                            <InputLabel for="user_id" value="User / pemilik laporan *" />
                            <select
                                id="user_id"
                                v-model="form.user_id"
                                required
                                class="mt-2 block w-full rounded-xl border-0 bg-white px-3 py-2 text-sm text-gray-700 shadow-inner ring-1 ring-gray-200 focus:ring-2 focus:ring-[#00529b]"
                            >
                                <option value="">-- Pilih user --</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.label }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.user_id" />
                        </div>

                        <div>
                            <InputLabel for="bagian" value="Bagian (GO ACTION) *" />
                            <select
                                id="bagian"
                                v-model="form.bagian"
                                required
                                class="mt-2 block w-full rounded-xl border-0 bg-white px-3 py-2 text-sm text-gray-700 shadow-inner ring-1 ring-gray-200 focus:ring-2 focus:ring-[#00529b]"
                            >
                                <option value="">-- Pilih bagian --</option>
                                <option v-for="d in departemenOptions" :key="d" :value="d">{{ d }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.bagian" />
                        </div>

                        <div>
                            <InputLabel for="nama_ruangan" value="Nama ruangan" />
                            <TextInput
                                id="nama_ruangan"
                                v-model="form.nama_ruangan"
                                type="text"
                                class="mt-2 block w-full"
                                placeholder="Opsional"
                            />
                            <InputError class="mt-2" :message="form.errors.nama_ruangan" />
                        </div>

                        <div>
                            <InputLabel for="penjelasan_aksi" value="Penjelasan aksi" />
                            <textarea
                                id="penjelasan_aksi"
                                v-model="form.penjelasan_aksi"
                                rows="3"
                                class="mt-2 block w-full rounded-xl border-0 bg-white px-3 py-2 text-sm text-gray-700 shadow-inner ring-1 ring-gray-200"
                                placeholder="Opsional (mis. impor massal dari Excel)"
                            />
                            <InputError class="mt-2" :message="form.errors.penjelasan_aksi" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="latitude" value="Latitude *" />
                                <TextInput id="latitude" v-model="form.latitude" type="text" class="mt-2 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.latitude" />
                            </div>
                            <div>
                                <InputLabel for="longitude" value="Longitude *" />
                                <TextInput id="longitude" v-model="form.longitude" type="text" class="mt-2 block w-full" required />
                                <InputError class="mt-2" :message="form.errors.longitude" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="excel_file" value="File Excel (.xlsx) *" />
                            <input
                                id="excel_file"
                                type="file"
                                accept=".xlsx,.xls,.xlsm,.csv,.txt"
                                class="mt-2 block w-full text-sm text-gray-700"
                                required
                                @change="onFile"
                            />
                            <p class="mt-1 text-xs text-gray-500">Maks. 10 MB. Format utama: Excel .xlsx (CSV tetap didukung untuk keperluan darurat).</p>
                            <InputError class="mt-2" :message="form.errors.excel_file" />
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                            <PrimaryButton type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Mengimpor...' : 'Impor ke DBR' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
