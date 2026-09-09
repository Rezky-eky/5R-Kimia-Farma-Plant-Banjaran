<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import MonthlyExcelExport from '@/Components/MonthlyExcelExport.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    items: { type: Object, required: true },
    isAdmin: { type: Boolean, default: false },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const searchForm = ref({
    search: props.filters.search || '',
});

watch(() => props.filters, (newFilters) => {
    searchForm.value.search = newFilters.search || '';
}, { deep: true });

const performSearch = () => {
    router.get(route('go_offer.index'), {
        search: searchForm.value.search.trim(),
        page: 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearSearch = () => {
    searchForm.value.search = '';
    performSearch();
};

const approveOffer = (id) => {
    if (confirm('Terima request ini?')) {
        router.post(route('go_offer.accept', id));
    }
};

const rejectOffer = (id) => {
    if (confirm('Tolak request ini?')) {
        router.post(route('go_offer.reject', id));
    }
};

const getRingkasStatusBadgeClass = (status) => {
    switch (status) {
        case 'available':
            return 'bg-gray-100 text-gray-700';
        case 'requested':
            return 'bg-amber-100 text-amber-800';
        case 'allocated':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};

const getRingkasStatusLabel = (status) => {
    switch (status) {
        case 'available':
            return 'available';
        case 'requested':
            return 'requested/interested';
        case 'allocated':
            return 'allocated/approved';
        default:
            return status || 'available';
    }
};
</script>

<template>
    <Head title="Go Offer - Ajukan Ambil & Approval" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Go Offer – Tawaran Barang DBR
                </h2>
                <BackToDashboard :admin="isAdmin" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <MonthlyExcelExport
                        :export-route="isAdmin ? 'admin.reports.go_offer.export' : 'reports.go_offer.export'"
                    />
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <form @submit.prevent="performSearch" class="flex flex-col gap-3 md:flex-row md:items-end">
                            <div class="flex-1">
                                <label for="go-offer-search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Pencarian
                                </label>
                                <input
                                    id="go-offer-search"
                                    v-model="searchForm.search"
                                    type="search"
                                    placeholder="Cari nama barang, creator, bagian, status..."
                                    class="block min-h-[42px] w-full rounded-lg border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-[#86a7a0] focus:outline-none focus:ring-2 focus:ring-[#86a7a0]/30"
                                />
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="submit"
                                    class="min-h-[42px] rounded-lg bg-[#789e98] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#668c86] hover:shadow-md"
                                >
                                    Cari
                                </button>
                                <button
                                    type="button"
                                    @click="clearSearch"
                                    class="min-h-[42px] rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50"
                                >
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="px-6 py-4 border-b border-gray-200">
                        <p class="text-sm text-gray-600">
                            {{ isAdmin ? 'Semua item Go Offer.' : 'Ajukan Ambil untuk request, creator meng-approve/reject.' }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Barang</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Creator</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Diminta oleh</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in items.data" :key="item.go_action_id + '_' + item.dbr_index" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ item.created_at }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                        {{ item.dbr_snapshot?.nama_barang || '-' }} ({{ item.dbr_snapshot?.jumlah }} {{ item.dbr_snapshot?.satuan }})
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ item.creator_bagian }} ({{ item.creator_name }})</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ item.ringkas_status === 'requested' || item.ringkas_status === 'allocated' ? (item.requested_by_name || '-') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                getRingkasStatusBadgeClass(item.ringkas_status),
                                            ]"
                                        >
                                            {{ getRingkasStatusLabel(item.ringkas_status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex justify-end gap-3">
                                            <!-- Ajukan Ambil: hanya user non-creator dan saat status available -->
                                            <template v-if="item.creator_user_id !== $page.props.auth?.user?.id">
                                                <Link
                                                    v-if="item.ringkas_status === 'available'"
                                                    :href="route('go_offer.create', { go_action_id: item.go_action_id, dbr_index: item.dbr_index, mode: 'request' })"
                                                    class="text-[#00529b] hover:text-[#004080] font-medium"
                                                >
                                                    Ajukan Ambil
                                                </Link>
                                                <span v-else class="text-gray-400">-</span>
                                            </template>

                                            <!-- Approval: hanya creator ketika status requested -->
                                            <template v-else>
                                                <button
                                                    v-if="item.ringkas_status === 'requested' && item.active_offer_id"
                                                    type="button"
                                                    @click="approveOffer(item.active_offer_id)"
                                                    class="text-[#00529b] hover:text-[#004080] font-medium"
                                                >
                                                    Terima
                                                </button>
                                                <button
                                                    v-if="item.ringkas_status === 'requested' && item.active_offer_id"
                                                    type="button"
                                                    @click="rejectOffer(item.active_offer_id)"
                                                    class="text-red-600 hover:text-red-800 font-medium"
                                                >
                                                    Tolak
                                                </button>
                                                <span v-else class="text-gray-400">-</span>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="items.data.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">Belum ada item Go Offer.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationBar :paginator="items" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
