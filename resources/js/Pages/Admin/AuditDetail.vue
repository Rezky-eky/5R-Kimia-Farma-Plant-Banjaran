<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
            <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                Laporan 5R Keseluruhan – Detail
            </h2>
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Foto Kegiatan</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="(foto, index) in goAction.fotos"
                            :key="index"
                            class="relative group rounded-lg overflow-hidden border border-gray-200"
                        >
                            <img
                                :src="foto"
                                :alt="`Foto kegiatan ${index + 1}`"
                                class="w-full h-48 object-cover transition-transform group-hover:scale-105"
                            />
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-opacity flex items-center justify-center">
                                <a
                                    :href="foto"
                                    target="_blank"
                                    class="opacity-0 group-hover:opacity-100 text-white font-semibold bg-blue-600 px-4 py-2 rounded-lg transition-opacity"
                                >
                                    Lihat Full Size
                                </a>
                            </div>
                        </div>
                    </div>
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

