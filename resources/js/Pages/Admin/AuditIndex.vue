<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import ReportStatusBadge from '@/Components/ReportStatusBadge.vue';
import PhotoGallery from '@/Components/PhotoGallery.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    laporan: {
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
            status: '',
            jenis: '',
        }),
    },
    filterLabel: {
        type: String,
        default: null,
    },
});

const searchForm = ref({
    search: props.filters.search || '',
    departemen: props.filters.departemen || '',
    status: props.filters.status || '',
    jenis: props.filters.jenis || '',
});

// Sync searchForm ketika props berubah (e.g. setelah navigasi paginasi)
watch(() => props.filters, (newFilters) => {
    searchForm.value.search = newFilters.search || '';
    searchForm.value.departemen = newFilters.departemen || '';
    searchForm.value.status = newFilters.status || '';
    searchForm.value.jenis = newFilters.jenis || '';
}, { deep: true });

const performSearch = () => {
    router.get(route('admin.audit.index'), {
        search: searchForm.value.search,
        departemen: searchForm.value.departemen,
        status: searchForm.value.status,
        jenis: searchForm.value.jenis,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchForm.value.search = '';
    searchForm.value.departemen = '';
    searchForm.value.status = '';
    searchForm.value.jenis = '';
    performSearch();
};

const detailUrl = (action) => {
    if (action.detail_id != null) {
        return route(action.detail_route, action.detail_id);
    }
    return route(action.detail_route);
};
</script>

<template>
    <Head title="Laporan 5R Keseluruhan - Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                        Laporan 5R Keseluruhan
                    </h2>
                    <p v-if="filterLabel" class="mt-1 text-sm text-[#00529b] font-medium">
                        Filter aktif: {{ filterLabel }}
                    </p>
                </div>
                <BackToDashboard admin />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    v-if="filterLabel"
                    class="mb-6 rounded-xl border border-[#00529b]/20 bg-blue-50 px-4 py-3 text-sm text-[#00529b]"
                >
                    {{ filterLabel }}.
                    <button
                        type="button"
                        class="ml-2 font-semibold underline hover:no-underline"
                        @click="clearFilters"
                    >
                        Tampilkan semua laporan
                    </button>
                </div>

                <!-- Filter & Search -->
                <div class="mb-6 rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <form @submit.prevent="performSearch" class="space-y-4 md:space-y-0 md:flex md:items-end md:gap-4">
                        <!-- Search Input -->
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

                        <!-- Departemen Filter -->
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

                        <!-- Filter Jenis Aksi -->
                        <div class="md:w-48">
                            <label for="jenis" class="block text-sm font-medium text-gray-700 mb-2">
                                Jenis Laporan
                            </label>
                            <select
                                id="jenis"
                                v-model="searchForm.jenis"
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                            >
                                <option value="">Semua</option>
                                <option value="go_action">Go Action saja</option>
                                <option value="go_boost">Go Boost saja</option>
                                <option value="go_care">Go Care saja</option>
                                <option value="go_offer">Go Offer saja</option>
                                <option value="go_sale">Go Sale saja</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="md:w-52">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Status audit / approval
                            </label>
                            <select
                                id="status"
                                v-model="searchForm.status"
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                            >
                                <option value="">Semua laporan</option>
                                <option value="pending">Pending — menunggu audit/approve</option>
                                <option value="audited">Audited — sudah diaudit/diseteujui</option>
                                <option value="rejected">Rejected — ditolak</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-300/50 transition duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
                            >
                                Cari
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="inline-flex items-center justify-center rounded-xl bg-gray-200 px-6 py-2.5 text-sm font-semibold text-gray-700 shadow-md transition hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                            >
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-2xl bg-white/90 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="px-8 py-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Laporan Data 5R Keseluruhan
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Total: {{ laporan.total }} laporan
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-700">No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-700">Nama Karyawan</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-700">NPP</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-700">Bagian</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-700">Nama Ruangan</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-700">Penjelasan Aksi</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-700">Foto / Jenis Aksi</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-700">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-700">Tanggal</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="(action, index) in laporan.data"
                                    :key="`${action.type}-${action.id}`"
                                    class="transition-colors hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                        {{ (laporan.current_page - 1) * laporan.per_page + index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                        {{ action.nama_karyawan }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ action.npp_karyawan }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ action.bagian }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ action.nama_ruangan || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs line-clamp-2">
                                        {{ action.penjelasan_aksi || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <PhotoGallery
                                                v-if="action.foto_url"
                                                :images="[action.foto_url]"
                                                :title="action.jenis_aksi"
                                                compact
                                            />
                                            <span
                                                v-else
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-xs font-medium text-gray-500"
                                            >
                                                —
                                            </span>
                                            <span class="text-xs font-semibold text-gray-700">{{ action.jenis_aksi }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <ReportStatusBadge :type="action.type" :status="action.status" />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ action.created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Link
                                                :href="detailUrl(action)"
                                                class="text-blue-600 hover:text-blue-900 font-semibold"
                                            >
                                                Detail →
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="laporan.data.length === 0">
                                    <td
                                        colspan="10"
                                        class="px-6 py-10 text-center text-sm text-gray-500"
                                    >
                                        Tidak ada data laporan yang ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <PaginationBar :paginator="laporan" item-label="hasil" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

