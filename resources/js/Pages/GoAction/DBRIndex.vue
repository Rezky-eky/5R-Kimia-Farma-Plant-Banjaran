<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();

const props = defineProps({
    dbrItems: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ status_tps: 'Semua' }),
    },
});

const statusTpsOptions = ['Diperlukan', 'Ragu-Ragu', 'Tidak Diperlukan', 'Semua'];
const selectedStatusFilter = ref(props.filters.status_tps ?? 'Semua');

const tableRows = computed(() => props.dbrItems.data ?? []);

const applyStatusFilter = () => {
    router.get(
        route('go_action.dbr_index'),
        { status_tps: selectedStatusFilter.value, page: 1 },
        { preserveState: true, preserveScroll: true }
    );
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'Diperlukan':
            return 'bg-blue-100 text-blue-700';
        case 'Ragu-Ragu':
            return 'bg-yellow-100 text-yellow-700';
        case 'Tidak Diperlukan':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};

/** Pemilik baris DBR (creator) — bandingkan ID dengan aman (string vs number dari JSON/Inertia). */
const isRowCreator = (item) =>
    Number(item.creator_user_id) === Number(page.props.auth?.user?.id);

/** Tombol Request (mutasi): non-creator, baris eligible, belum ada request aktif. */
const canRequestMutation = (item) => {
    if (item.ringkas_status !== 'available') {
        return false;
    }
    if (typeof item.mutation_eligible === 'boolean') {
        return item.mutation_eligible;
    }
    return !item.distribution_type || ['offer', 'sale'].includes(item.distribution_type);
};

const approveOffer = (id) => {
    if (confirm('Setujui permintaan mutasi ini?')) {
        router.post(route('go_offer.accept', id));
    }
};

const rejectOffer = (id) => {
    if (confirm('Tolak permintaan mutasi ini?')) {
        router.post(route('go_offer.reject', id));
    }
};

const approveSale = (id) => {
    if (confirm('Setujui request pembelian (legacy Sale) ini?')) {
        router.post(route('go_sale.accept', id));
    }
};

const rejectSale = (id) => {
    if (confirm('Tolak request pembelian (legacy Sale) ini?')) {
        router.post(route('go_sale.reject', id));
    }
};

</script>

<template>
    <Head title="Daftar Barang Ringkas (DBR)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Daftar Barang Ringkas (DBR)
                </h2>
                <BackToDashboard />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white/90 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="px-8 py-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Semua Data Daftar Barang Ringkas
                                </h3>
                                <p class="mt-2 text-sm text-gray-600">
                                    Daftar lengkap barang yang tercatat dalam laporan GO ACTION
                                </p>
                            </div>
                            
                            <!-- Filter Dropdown -->
                            <div class="flex items-center gap-3">
                                <label for="status_filter" class="text-sm font-medium text-gray-700">
                                    Filter Status TPS:
                                </label>
                                <select
                                    id="status_filter"
                                    v-model="selectedStatusFilter"
                                    class="rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                    @change="applyStatusFilter"
                                >
                                    <option
                                        v-for="status in statusTpsOptions"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ status }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/70">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-800">
                                        No
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-800">
                                        Tanggal
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-800">
                                        Bagian
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-800">
                                        Nama Ruangan
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-800">
                                        Nama Barang
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-800">
                                        Jumlah
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-800">
                                        No Aktiva/SAP
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-800">
                                        Status di TPS
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-800">
                                        Tindakan Barang
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-bold uppercase tracking-widest text-gray-800">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white/95 divide-y divide-gray-100">
                                <tr
                                    v-for="(item, index) in tableRows"
                                    :key="item.id"
                                    class="transition-colors hover:bg-gray-50/40"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 text-center">
                                        {{ (dbrItems.from ?? 1) + index }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ item.tanggal }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                        {{ item.bagian }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ item.nama_ruangan }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-800 max-w-xs">
                                        {{ item.nama_barang }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ item.jumlah }} {{ item.satuan }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ item.no_aktiva_sap }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold', getStatusBadgeClass(item.status_tps)]"
                                        >
                                            {{ item.status_tps }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                        {{ item.tindakan_barang || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <!-- Pemilik entri DBR: tidak bisa Offer; bisa Terima/Tolak jika ada request -->
                                            <template v-if="isRowCreator(item)">
                                                <template v-if="item.ringkas_status === 'requested' && item.active_offer_id">
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center rounded-full bg-[#00529b]/10 px-3 py-1 text-xs font-semibold text-[#00529b] hover:bg-[#00529b]/20 transition"
                                                        @click="approveOffer(item.active_offer_id)"
                                                    >
                                                        Terima
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 transition"
                                                        @click="rejectOffer(item.active_offer_id)"
                                                    >
                                                        Tolak
                                                    </button>
                                                </template>
                                                <template v-else-if="item.ringkas_status === 'requested' && item.active_sale_request_id">
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center rounded-full bg-[#00529b]/10 px-3 py-1 text-xs font-semibold text-[#00529b] hover:bg-[#00529b]/20 transition"
                                                        @click="approveSale(item.active_sale_request_id)"
                                                    >
                                                        Terima
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 transition"
                                                        @click="rejectSale(item.active_sale_request_id)"
                                                    >
                                                        Tolak
                                                    </button>
                                                </template>
                                                <span v-else class="text-xs text-gray-400">Barang Anda</span>
                                            </template>

                                            <!-- Bagian lain: tautan Request → form mutasi (Go Offer) -->
                                            <template v-else>
                                                <Link
                                                    v-if="canRequestMutation(item)"
                                                    :href="route('go_offer.create', { go_action_id: item.go_action_id, dbr_index: item.dbr_index, mode: 'request' })"
                                                    class="inline-flex items-center rounded-full bg-[#00529b]/10 px-3 py-1 text-xs font-semibold text-[#00529b] hover:bg-[#00529b]/20 transition"
                                                >
                                                    Request
                                                </Link>
                                                <span v-else-if="item.ringkas_status === 'requested'" class="text-xs font-semibold text-amber-800">
                                                    Sedang diajukan — menunggu pemilik DBR
                                                </span>
                                                <span v-else-if="item.ringkas_status === 'allocated'" class="text-xs font-medium text-green-700">
                                                    Mutasi disetujui
                                                </span>
                                                <span v-else-if="item.ringkas_status === 'completed'" class="text-xs font-medium text-green-700">
                                                    Selesai
                                                </span>
                                                <span v-else class="text-xs text-gray-400">—</span>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tableRows.length === 0">
                                    <td
                                        colspan="10"
                                        class="px-6 py-10 text-center text-sm text-gray-500"
                                    >
                                        Tidak ada data DBR yang ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <PaginationBar :paginator="dbrItems" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

