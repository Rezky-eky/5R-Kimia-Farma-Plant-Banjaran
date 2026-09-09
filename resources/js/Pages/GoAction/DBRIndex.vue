<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import MonthlyExcelExport from '@/Components/MonthlyExcelExport.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const page = usePage();

const props = defineProps({
    dbrItems: {
        type: Object,
        required: true,
    },
    destroyedItems: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ status_tps: 'Semua' }),
    },
});

const statusTpsOptions = ['Diperlukan', 'Ragu-Ragu', 'Tidak Diperlukan', 'Semua'];
const selectedStatusFilter = ref(props.filters.status_tps ?? 'Semua');
const searchQuery = ref(props.filters.search ?? '');
const showAddForm = ref(false);
const addForm = ref({
    bagian: page.props.auth?.user?.bagian ?? '',
    nama_ruangan: '', nama_barang: '', jumlah: 1, satuan: 'Unit',
    distribution_type: 'offer', harga: '', no_aktiva_sap: '',
    kondisi_barang: 'baik', status_tps: 'Diperlukan', tindakan_barang: '',
});
const addErrors = ref({});
const showDestroyedHistory = ref(false);

const tableRows = computed(() => props.dbrItems.data ?? []);

watch(() => props.filters, (newFilters) => {
    selectedStatusFilter.value = newFilters.status_tps ?? 'Semua';
    searchQuery.value = newFilters.search ?? '';
}, { deep: true });

const applyFilters = () => {
    router.get(
        route('go_action.dbr_index'),
        { status_tps: selectedStatusFilter.value, search: searchQuery.value.trim(), page: 1 },
        { preserveState: true, preserveScroll: true }
    );
};

const clearFilters = () => {
    selectedStatusFilter.value = 'Semua';
    searchQuery.value = '';
    applyFilters();
};

const submitAdd = () => {
    router.post(route('go_action.dbr_store'), addForm.value, {
        preserveScroll: true,
        onError: (errors) => { addErrors.value = errors; },
        onSuccess: () => {
            showAddForm.value = false;
            addErrors.value = {};
            addForm.value = { ...addForm.value, nama_barang: '', jumlah: 1, harga: '', no_aktiva_sap: '', tindakan_barang: '' };
        },
    });
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'Diperlukan':
            return 'bg-blue-100 text-blue-700';
        case 'Ragu-Ragu':
            return 'bg-yellow-100 text-yellow-700';
        case 'Tidak Diperlukan':
            return 'bg-red-100 text-red-700';
        case 'destroyed':
            return 'bg-gray-200 text-gray-700';
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

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-xl bg-white shadow-lg shadow-slate-200/70 ring-1 ring-slate-200/80">
                    <div class="border-b border-slate-200 bg-gradient-to-r from-white to-slate-50 px-5 py-5 sm:px-6">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#00529b]/10 text-[#00529b]">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7.5 12 3 4 7.5m16 0v9L12 21l-8-4.5v-9m16 0-8 4.5m-8-4.5 8 4.5m0 0V21" /></svg>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-bold tracking-tight text-slate-900">Data Barang Ringkas</h3>
                                        <p class="mt-0.5 text-sm text-slate-500">Kelola barang, status TPS, dan tindak lanjutnya.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex w-full flex-col gap-3 xl:max-w-3xl">
                                <div class="flex flex-col gap-3 md:flex-row md:items-end">
                                    <div class="min-w-0 flex-1">
                                        <label for="dbr-search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari barang</label>
                                        <div class="relative">
                                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                                            <input id="dbr-search" v-model="searchQuery" type="search" placeholder="Nama barang, bagian, atau pelapor" class="w-full rounded-lg border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 shadow-sm transition focus:border-[#00529b] focus:outline-none focus:ring-2 focus:ring-[#00529b]/20" @keyup.enter="applyFilters" />
                                        </div>
                                    </div>
                                    <div class="w-full md:w-52">
                                        <label for="status_filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status TPS</label>
                                        <select id="status_filter" v-model="selectedStatusFilter" class="w-full rounded-lg border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-[#00529b] focus:ring-2 focus:ring-[#00529b]" @change="applyFilters">
                                            <option v-for="status in statusTpsOptions" :key="status" :value="status">{{ status }}</option>
                                        </select>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" @click="clearFilters" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">Reset</button>
                                        <button type="button" @click="showAddForm = !showAddForm" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#00529b] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#004080]"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14" /></svg>Tambah barang</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 border-t border-slate-200 pt-4">
                            <div v-if="showAddForm" class="mb-4 rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                                <form class="grid grid-cols-1 gap-3 md:grid-cols-4" @submit.prevent="submitAdd">
                                    <input v-model="addForm.nama_barang" required placeholder="Nama barang" class="rounded-lg border-gray-200 text-sm" />
                                    <input v-model="addForm.bagian" required placeholder="Bagian" class="rounded-lg border-gray-200 text-sm" />
                                    <input v-model="addForm.nama_ruangan" placeholder="Nama ruangan" class="rounded-lg border-gray-200 text-sm" />
                                    <input v-model.number="addForm.jumlah" required min="1" type="number" placeholder="Jumlah" class="rounded-lg border-gray-200 text-sm" />
                                    <select v-model="addForm.satuan" class="rounded-lg border-gray-200 text-sm"><option>Unit</option><option>Pcs</option><option>Box</option><option>Kg</option><option>Liter</option><option>Meter</option><option>Set</option></select>
                                    <select v-model="addForm.distribution_type" class="rounded-lg border-gray-200 text-sm"><option value="offer">Go Offer</option><option value="sale">Go Sale</option><option value="destroyed">Dimusnahkan</option></select>
                                    <input v-if="addForm.distribution_type === 'sale'" v-model="addForm.harga" required type="number" min="0" placeholder="Harga" class="rounded-lg border-gray-200 text-sm" />
                                    <input v-model="addForm.no_aktiva_sap" placeholder="No Aktiva/SAP" class="rounded-lg border-gray-200 text-sm" />
                                    <select v-model="addForm.kondisi_barang" class="rounded-lg border-gray-200 text-sm"><option value="baik">Baik</option><option value="rusak">Rusak</option><option value="kadaluarsa">Kadaluarsa</option><option value="lainnya">Lainnya</option></select>
                                    <select v-model="addForm.status_tps" class="rounded-lg border-gray-200 text-sm"><option>Diperlukan</option><option>Ragu-Ragu</option><option>Tidak Diperlukan</option></select>
                                    <input v-model="addForm.tindakan_barang" placeholder="Tindakan barang" class="rounded-lg border-gray-200 text-sm md:col-span-2" />
                                    <div class="flex gap-2 md:col-span-4"><button type="submit" class="rounded-lg bg-[#00529b] px-4 py-2 text-sm font-semibold text-white">Simpan</button><button type="button" @click="showAddForm = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm">Batal</button></div>
                                </form>
                                <p v-if="Object.keys(addErrors).length" class="mt-2 text-sm text-red-600">Periksa kembali field yang wajib diisi.</p>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-xs text-slate-500">Unduh laporan berdasarkan satu bulan atau rentang bulan.</p>
                                <MonthlyExcelExport export-route="reports.dbr.export" :allow-range="true" compact />
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        No
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        Tanggal
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        Bagian
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        Nama Ruangan
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        Nama Barang
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        Jumlah
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        No Aktiva/SAP
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        Status di TPS
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        Tindakan Barang
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500">
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
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div class="flex min-w-[140px] flex-col gap-1">
                                            <span class="w-fit rounded-md bg-blue-50 px-2 py-1 text-xs font-bold text-[#00529b]">
                                                {{ item.distribution_type === 'sale' ? 'Go Sale' : item.distribution_type === 'offer' ? 'Go Offer' : '-' }}
                                            </span>
                                            <span v-if="item.distribution_type === 'sale' && item.harga !== null" class="text-xs text-gray-500">
                                            Rp {{ Number(item.harga).toLocaleString('id-ID') }}
                                            </span>
                                            <span class="max-w-[180px] whitespace-normal break-words text-xs leading-5 text-slate-500">
                                                {{ item.tindakan_barang || 'Tidak ada deskripsi' }}
                                            </span>
                                        </div>
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

                    <section v-if="destroyedItems.length" class="border-t border-gray-200 bg-gray-50/70 px-6 py-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Riwayat Barang Dimusnahkan</h3>
                                <p class="mt-1 text-xs text-slate-500">{{ destroyedItems.length }} barang tercatat sebagai dimusnahkan.</p>
                            </div>
                            <button type="button" @click="showDestroyedHistory = !showDestroyedHistory" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                                {{ showDestroyedHistory ? 'Sembunyikan riwayat' : 'Lihat riwayat' }}
                                <svg class="h-4 w-4 transition-transform" :class="showDestroyedHistory ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" /></svg>
                            </button>
                        </div>
                        <div v-if="showDestroyedHistory" class="mt-4 overflow-x-auto rounded-xl bg-white ring-1 ring-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-700">Tanggal</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-700">Nama Barang</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-700">Jumlah</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-700">Bagian</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-700">No Aktiva/SAP</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-700">Status TPS</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="item in destroyedItems" :key="`destroyed-${item.id}`">
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ item.tanggal }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ item.nama_barang }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ item.jumlah }} {{ item.satuan }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ item.bagian }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ item.no_aktiva_sap }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ item.status_tps }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

