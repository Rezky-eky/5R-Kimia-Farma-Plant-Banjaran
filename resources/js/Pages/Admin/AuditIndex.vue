<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

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
});

const searchForm = useForm({
    search: props.filters.search || '',
    departemen: props.filters.departemen || '',
    status: props.filters.status || '',
    jenis: props.filters.jenis || '',
});

const performSearch = () => {
    router.get(route('admin.audit.index'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchForm.search = '';
    searchForm.departemen = '';
    searchForm.status = '';
    searchForm.jenis = '';
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
            <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                Laporan 5R Keseluruhan
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
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

                        <!-- Status Filter (untuk Go Action) -->
                        <div class="md:w-48">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Status
                            </label>
                            <select
                                id="status"
                                v-model="searchForm.status"
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                            >
                                <option value="">Semua</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-2">
                            <button
                                type="submit"
                                :disabled="searchForm.processing"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-300/50 transition duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="searchForm.processing" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
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
                                            <div
                                                v-if="action.foto_url"
                                                class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100"
                                            >
                                                <img
                                                    :src="action.foto_url"
                                                    :alt="action.jenis_aksi"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
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
                                        <span
                                            v-if="action.type === 'go_action' && action.status === 'Pending'"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700"
                                        >
                                            Pending
                                        </span>
                                        <span
                                            v-else-if="action.type === 'go_action' && action.status === 'Approved'"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700"
                                        >
                                            Approved
                                        </span>
                                        <span
                                            v-else-if="action.type === 'go_action' && action.status === 'Rejected'"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700"
                                        >
                                            Rejected
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700"
                                        >
                                            {{ action.status }}
                                        </span>
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

                    <!-- Pagination -->
                    <div v-if="laporan.last_page > 1" class="px-8 py-4 border-t border-gray-200 flex flex-wrap items-center justify-between gap-4">
                        <div class="text-sm text-gray-700">
                            Menampilkan {{ laporan.from ?? 0 }} sampai {{ laporan.to ?? 0 }} dari {{ laporan.total }} hasil
                        </div>
                        <div class="flex gap-2">
                            <template v-for="(link, i) in laporan.links" :key="i">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-4 py-2 rounded-lg text-sm font-medium transition',
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-400 cursor-not-allowed"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

