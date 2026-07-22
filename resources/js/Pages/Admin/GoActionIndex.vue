<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import ReportStatusBadge from '@/Components/ReportStatusBadge.vue';
import PhotoGallery from '@/Components/PhotoGallery.vue';
import MonthlyExcelExport from '@/Components/MonthlyExcelExport.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    goActions: {
        type: Object,
        required: true,
    },
    departements: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            departemen: '',
        }),
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const searchForm = ref({
    search: props.filters.search || '',
    departemen: props.filters.departemen || '',
});

// Sync searchForm ketika props berubah (e.g. setelah navigasi paginasi)
watch(() => props.filters, (newFilters) => {
    searchForm.value.search = newFilters.search || '';
    searchForm.value.departemen = newFilters.departemen || '';
}, { deep: true });

const performSearch = () => {
    router.get(route('admin.go_action.index'), {
        search: searchForm.value.search,
        departemen: searchForm.value.departemen,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchForm.value.search = '';
    searchForm.value.departemen = '';
    performSearch();
};

const showDetail = ref({});

const toggleDetail = (id) => {
    showDetail.value[id] = !showDetail.value[id];
};
</script>

<template>
    <Head title="Data GO ACTION - Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Data GO ACTION
                </h2>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <BackToDashboard admin />
                    <Link
                        v-if="isAdmin"
                        :href="route('admin.go_action.weekly_realization')"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg hover:bg-blue-700"
                    >
                        Realisasi Mingguan
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-none px-4 sm:px-6 lg:px-8">
                <!-- Filter & Search -->
                <div class="mb-6 rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <form @submit.prevent="performSearch" class="space-y-4 md:space-y-0 md:flex md:items-end md:gap-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                                Pencarian
                            </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Cari berdasarkan nama, NPP, bagian, atau penjelasan..."
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                            />
                        </div>

                        <div class="md:w-64">
                            <label for="departemen" class="block text-sm font-medium text-gray-700 mb-2">
                                Filter Departemen
                            </label>
                            <select
                                id="departemen"
                                v-model="searchForm.departemen"
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                            >
                                <option value="">Semua Departemen</option>
                                <option
                                    v-for="dept in departements"
                                    :key="dept"
                                    :value="dept"
                                >
                                    {{ dept }}
                                </option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-300/50 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
                            >
                                Cari
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="rounded-xl bg-gray-200 px-6 py-2.5 text-sm font-semibold text-gray-700 shadow-lg transition hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                            >
                                Reset
                            </button>
                        </div>
                    </form>
                    <div class="mt-4 border-t border-gray-100 pt-4" v-if="isAdmin">
                        <MonthlyExcelExport export-route="admin.reports.go_action.export" />
                    </div>
                </div>

                <!-- Data Table -->
                <div class="rounded-2xl bg-white/90 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60 overflow-hidden">
                    <div class="overflow-x-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Tanggal
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Karyawan
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Bagian
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Ruangan
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 max-w-xs">
                                        Penjelasan Aksi
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template v-for="goAction in goActions.data" :key="goAction.id">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {{ goAction.created_at }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div>
                                                <div class="font-medium">{{ goAction.nama_karyawan }}</div>
                                                <div class="text-xs text-gray-500">NPP: {{ goAction.npp_karyawan }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ goAction.bagian }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ goAction.nama_ruangan || '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 max-w-xs line-clamp-2" :title="goAction.penjelasan_aksi || ''">
                                            {{ goAction.penjelasan_aksi || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            <ReportStatusBadge type="go_action" :status="goAction.status" />
                                            <div v-if="goAction.score !== null" class="text-xs text-gray-500 mt-1">
                                                Score: {{ goAction.score }}/10
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <button
                                                @click="toggleDetail(goAction.id)"
                                                class="text-blue-600 hover:text-blue-900 transition-colors"
                                            >
                                                {{ showDetail[goAction.id] ? 'Sembunyikan' : 'Lihat Detail' }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="showDetail[goAction.id]" class="bg-gray-50">
                                        <td colspan="7" class="border-t border-gray-200 p-6">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Left Column -->
                                                <div class="space-y-4">
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Penjelasan Aksi</h4>
                                                        <p class="text-sm text-gray-700 bg-white p-3 rounded-lg">
                                                            {{ goAction.penjelasan_aksi || '-' }}
                                                        </p>
                                                    </div>

                                                    <div v-if="goAction.latitude && goAction.longitude">
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Koordinat GPS</h4>
                                                        <p class="text-sm text-gray-700 bg-white p-3 rounded-lg">
                                                            Lat: {{ goAction.latitude }}, Long: {{ goAction.longitude }}
                                                        </p>
                                                    </div>

                                                    <div v-if="goAction.list_barang_ringkas && goAction.list_barang_ringkas.length > 0">
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Daftar Barang Ringkas ({{ goAction.list_barang_ringkas.length }})</h4>
                                                        <div class="bg-white p-3 rounded-lg space-y-2 max-h-64 overflow-y-auto">
                                                            <div
                                                                v-for="(barang, index) in goAction.list_barang_ringkas"
                                                                :key="index"
                                                                class="border-b border-gray-100 pb-2 last:border-0"
                                                            >
                                                                <div class="font-medium text-sm">{{ barang.nama_barang }}</div>
                                                                <div class="text-xs text-gray-600">
                                                                    Jumlah: {{ barang.jumlah }} {{ barang.satuan }} |
                                                                    Kondisi: {{ barang.kondisi_barang }} |
                                                                    Status TPS: {{ barang.status_tps }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Right Column -->
                                                <div class="space-y-4">
                                                    <PhotoGallery
                                                        :images="goAction.fotos"
                                                        title="Foto Kegiatan"
                                                        grid-class="grid-cols-2 sm:grid-cols-3"
                                                    />

                                                    <div v-if="goAction.auditor_name">
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Auditor</h4>
                                                        <p class="text-sm text-gray-700 bg-white p-3 rounded-lg">
                                                            {{ goAction.auditor_name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <PaginationBar :paginator="goActions" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

