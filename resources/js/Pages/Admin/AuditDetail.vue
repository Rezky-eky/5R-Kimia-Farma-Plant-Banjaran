<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PhotoGallery from '@/Components/PhotoGallery.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    goAction: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Laporan 5R Keseluruhan - Detail" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Laporan 5R Keseluruhan – Detail
                </h2>
                <BackToDashboard admin />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Informasi Laporan -->
                <div class="rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Laporan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Nama Karyawan</p>
                            <p class="text-base font-medium text-gray-900">{{ goAction.nama_karyawan }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">NPP</p>
                            <p class="text-base font-medium text-gray-900">{{ goAction.npp_karyawan }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Bagian</p>
                            <p class="text-base font-medium text-gray-900">{{ goAction.bagian }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Ruangan</p>
                            <p class="text-base font-medium text-gray-900">{{ goAction.nama_ruangan || '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-500">Penjelasan Aksi</p>
                            <p class="text-base text-gray-900 mt-1">{{ goAction.penjelasan_aksi || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tanggal Laporan</p>
                            <p class="text-base font-medium text-gray-900">{{ goAction.created_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Foto Kegiatan -->
                <div v-if="goAction.fotos && goAction.fotos.length > 0" class="rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <PhotoGallery
                        :images="goAction.fotos"
                        title="Foto Kegiatan"
                        grid-class="grid-cols-1 sm:grid-cols-2 lg:grid-cols-3"
                        thumbnail-height-class="h-48"
                    />
                </div>

                <!-- Daftar Barang Ringkas (DBR) -->
                <div v-if="goAction.list_barang_ringkas && goAction.list_barang_ringkas.length > 0" class="rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Daftar Barang Hasil Ringkas (DBR)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700">Nama Barang</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700">Jumlah</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700">Satuan</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700">Kondisi</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700">Status TPS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(barang, index) in goAction.list_barang_ringkas" :key="index">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ barang.nama_barang }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ barang.jumlah }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ barang.satuan }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ barang.kondisi_barang }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ barang.status_tps }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

