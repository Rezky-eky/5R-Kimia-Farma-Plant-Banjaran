<script setup>
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    total_records_all: {
        type: Number,
        default: 0,
    },
    user_total_records: {
        type: Number,
        default: 0,
    },
    total_go_actions: {
        type: Number,
        default: 0,
    },
    total_go_boosts: {
        type: Number,
        default: 0,
    },
    total_go_cares: {
        type: Number,
        default: 0,
    },
    user_go_actions: {
        type: Number,
        default: 0,
    },
    user_ringkas_items: {
        type: Number,
        default: 0,
    },
    user_go_boosts: {
        type: Number,
        default: 0,
    },
    user_go_cares: {
        type: Number,
        default: 0,
    },
    user_go_offers: {
        type: Number,
        default: 0,
    },
    user_go_sales: {
        type: Number,
        default: 0,
    },
    user_go_checks: {
        type: Number,
        default: 0,
    },
    user_activity_breakdown: {
        type: Array,
        default: () => [],
    },
    recent_user_records: {
        type: Array,
        default: () => [],
    },
    show_go_check: {
        type: Boolean,
        default: false,
    },
    laporan_bulanan: {
        type: Array,
        default: () => [],
    },
});

const activityBreakdown = computed(() => props.user_activity_breakdown || []);
const maxActivityValue = computed(() => {
    if (!activityBreakdown.value.length) {
        return 1;
    }

    return Math.max(1, ...activityBreakdown.value.map((item) => item.count ?? 0));
});

const recentRecords = computed(() => props.recent_user_records || []);
const selectedModule = ref('All');
const currentPage = ref(1);
const pageSize = 10;
const expandedRows = ref([]);
const moduleOptions = computed(() => [
    { label: 'Semua Modul', value: 'All' },
    ...activityBreakdown.value.map((item) => ({ label: item.label, value: item.label })),
]);
const filteredRecords = computed(() => {
    if (selectedModule.value === 'All') {
        return recentRecords.value;
    }

    return recentRecords.value.filter((record) => record.module === selectedModule.value);
});

const pageCount = computed(() => Math.max(1, Math.ceil(filteredRecords.value.length / pageSize)));
const pagedRecords = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return filteredRecords.value.slice(start, start + pageSize);
});

const hasNextPage = computed(() => currentPage.value < pageCount.value);
const hasPreviousPage = computed(() => currentPage.value > 1);

watch(selectedModule, () => {
    currentPage.value = 1;
    expandedRows.value = [];
});

const toggleRowDetails = (id) => {
    const index = expandedRows.value.indexOf(id);
    if (index >= 0) {
        expandedRows.value.splice(index, 1);
    } else {
        expandedRows.value.push(id);
    }
};

const isRowExpanded = (id) => expandedRows.value.includes(id);

const fallbackData = [
    { bulan: 'Jan', open: 12, closed: 7 },
    { bulan: 'Feb', open: 15, closed: 9 },
    { bulan: 'Mar', open: 11, closed: 13 },
    { bulan: 'Apr', open: 18, closed: 14 },
    { bulan: 'Mei', open: 16, closed: 17 },
    { bulan: 'Jun', open: 14, closed: 19 },
];

const chartData = computed(() =>
    props.laporan_bulanan && props.laporan_bulanan.length
        ? props.laporan_bulanan
        : fallbackData,
);

const maxValue = computed(() => {
    const { value } = chartData;
    if (!value.length) {
        return 1;
    }

    return Math.max(
        1,
        ...value.map((item) => Math.max(item.open ?? 0, item.closed ?? 0)),
    );
});

const paddingTop = 8;
const paddingBottom = 8;

const generatePoints = (key) => {
    const data = chartData.value;
    if (!data.length) {
        return [];
    }

    const stepX = data.length > 1 ? 100 / (data.length - 1) : 100;

    return data.map((item, index) => {
        const x = data.length > 1 ? index * stepX : 50;
        const ratio = (item[key] ?? 0) / maxValue.value;
        const y = 100 - paddingBottom - ratio * (100 - paddingTop - paddingBottom);

        return {
            x: Number(x.toFixed(2)),
            y: Number(Math.max(paddingTop, Math.min(100 - paddingBottom, y)).toFixed(2)),
            value: item[key] ?? 0,
            label: item.bulan,
        };
    });
};

const buildPath = (key) => {
    const pts = generatePoints(key);
    if (!pts.length) {
        return '';
    }

    return pts
        .map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`)
        .join(' ');
};

const openPoints = computed(() => generatePoints('open'));
const closedPoints = computed(() => generatePoints('closed'));
const openPath = computed(() => buildPath('open'));
const closedPath = computed(() => buildPath('closed'));

const gridLines = computed(() => {
    const lines = 4;
    return Array.from({ length: lines + 1 }, (_, idx) => {
        const ratio = idx / lines;
        const value = Math.round(maxValue.value * (1 - ratio));
        const y = paddingTop + ratio * (100 - paddingTop - paddingBottom);
        return { y: Number(y.toFixed(2)), value };
    });
});
</script>

<template>
    <Head title="Dashboard Kinerja 5R Kimia Farma Plant Banjaran" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-2xl font-bold leading-tight text-gray-900"
            >
                Dashboard Kinerja 5R Kimia Farma Plant Banjaran
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- Ringkasan Data 5R dan Personal -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl bg-gradient-to-br from-sky-50 via-white to-sky-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Data Masuk</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Semua Modul 5R</h4>
                            </div>
                            <span class="text-xl">📦</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold" style="color: #00529b;">{{ total_records_all }}</span>
                            <span class="text-xs text-gray-500">rekaman</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Jumlah total data yang masuk ke seluruh modul 5R.</p>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-emerald-50 via-white to-emerald-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Aktivitas Saya</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Total Data Pribadi</h4>
                            </div>
                            <span class="text-xl">👤</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold" style="color: #047857;">{{ user_total_records }}</span>
                            <span class="text-xs text-gray-500">rekaman</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Ringkasan semua data yang Anda input dan ikuti.</p>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-rose-50 via-white to-rose-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Go Action</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Aksi Saya</h4>
                            </div>
                            <span class="text-xl">🧹</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-red-700">{{ user_go_actions }}</span>
                            <span class="text-xs text-gray-500">aksi</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Jumlah GO ACTION yang Anda buat.</p>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-slate-50 via-white to-slate-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Go Boost</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Temuan Saya</h4>
                            </div>
                            <span class="text-xl">🔍</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold" style="color: #0f172a;">{{ user_go_boosts }}</span>
                            <span class="text-xs text-gray-500">temuan</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Jumlah GO BOOST yang Anda laporkan.</p>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-amber-50 via-white to-amber-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Barang Ringkas</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Total Item</h4>
                            </div>
                            <span class="text-xl">📦</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-yellow-600">{{ user_ringkas_items }}</span>
                            <span class="text-xs text-gray-500">item</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Jumlah barang ringkas yang Anda catat.</p>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-sky-50 via-white to-sky-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Go Care</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Perbaikan Saya</h4>
                            </div>
                            <span class="text-xl">🛠️</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold" style="color: #0c4a6e;">{{ user_go_cares }}</span>
                            <span class="text-xs text-gray-500">perbaikan</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Jumlah GO CARE yang Anda submit.</p>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-violet-50 via-white to-violet-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Go Offer</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Tawaran Saya</h4>
                            </div>
                            <span class="text-xl">✉️</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold" style="color: #7c3aed;">{{ user_go_offers }}</span>
                            <span class="text-xs text-gray-500">tawaran</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Jumlah GO OFFER yang Anda buat.</p>
                    </div>

                    <div class="rounded-2xl bg-gradient-to-br from-emerald-50 via-white to-emerald-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Go Sale</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Mutasi Saya</h4>
                            </div>
                            <span class="text-xl">💰</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold" style="color: #166534;">{{ user_go_sales }}</span>
                            <span class="text-xs text-gray-500">transaksi</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Jumlah Go Sale yang melibatkan Anda.</p>
                    </div>

                    <div v-if="show_go_check" class="rounded-2xl bg-gradient-to-br from-rose-50 via-white to-rose-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Go Check</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Pemeriksaan Saya</h4>
                            </div>
                            <span class="text-xl">✔️</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-red-700">{{ user_go_checks }}</span>
                            <span class="text-xs text-gray-500">cek</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Jumlah Go Check yang terkait dengan Anda.</p>
                    </div>
                </div>

                <!-- Tren Kinerja -->
                <div class="rounded-2xl bg-white/90 p-6 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Tren Kinerja 5R (6 Bulan Terakhir)</h3>
                            <p class="text-sm text-gray-500">Perbandingan jumlah temuan OPEN dan CLOSED dari laporan bulanan.</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                                <span class="h-2 w-6 rounded-full bg-rose-300"></span>
                                OPEN
                            </div>
                            <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                                <span class="h-2 w-6 rounded-full" style="background-color: #00529b;"></span>
                                CLOSED
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="relative">
                            <svg
                                class="h-72 w-full text-blue-200"
                                viewBox="0 0 100 100"
                                preserveAspectRatio="none"
                            >
                                <g v-for="line in gridLines" :key="line.y">
                                    <line
                                        :x1="0"
                                        :x2="100"
                                        :y1="line.y"
                                        :y2="line.y"
                                        stroke="currentColor"
                                        stroke-width="0.6"
                                        stroke-dasharray="4 4"
                                        opacity="0.35"
                                    />
                                    <text
                                        :x="0"
                                        :y="line.y - 1"
                                        class="fill-gray-400 text-[2.8px]"
                                    >
                                        {{ line.value }}
                                    </text>
                                </g>

                                <path
                                    v-if="closedPath"
                                    :d="closedPath"
                                    fill="none"
                                    stroke="#3b82f6"
                                    stroke-width="2.2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    v-if="openPath"
                                    :d="openPath"
                                    fill="none"
                                    stroke="#fb7185"
                                    stroke-width="2.2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                                <g v-for="point in closedPoints" :key="`closed-${point.label}`">
                                    <circle
                                        :cx="point.x"
                                        :cy="point.y"
                                        r="1.5"
                                        fill="#3b82f6"
                                        stroke="white"
                                        stroke-width="0.6"
                                    />
                                </g>
                                <g v-for="point in openPoints" :key="`open-${point.label}`">
                                    <circle
                                        :cx="point.x"
                                        :cy="point.y"
                                        r="1.5"
                                        fill="#fb7185"
                                        stroke="white"
                                        stroke-width="0.6"
                                    />
                                </g>
                            </svg>
                        </div>

                        <div class="mt-4 grid grid-cols-6 gap-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <span v-for="item in chartData" :key="`label-${item.bulan}`">
                                {{ item.bulan }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-3">
                    <div class="rounded-2xl bg-white/90 p-6 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Distribusi Aktivitas Saya</h3>
                                <p class="text-sm text-gray-500">Persentase interaksi setiap modul dalam data pribadi Anda.</p>
                            </div>
                            <span class="text-2xl">📈</span>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div v-for="item in activityBreakdown" :key="item.label">
                                <div class="flex items-center justify-between text-sm text-gray-600">
                                    <span>{{ item.label }}</span>
                                    <span>{{ item.count }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-sky-500"
                                        :style="{ width: `${Math.round(((item.count ?? 0) / maxActivityValue.value) * 100)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-2 rounded-2xl bg-white/90 p-6 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Aktivitas Terakhir Saya</h3>
                                <p class="text-sm text-gray-500">Ringkasan entri data terbaru yang Anda masukkan.</p>
                            </div>
                            <span class="text-2xl">📝</span>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <template v-for="option in moduleOptions" :key="option.value">
                                    <button
                                        type="button"
                                        @click="selectedModule = option.value"
                                        :class="selectedModule === option.value ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-slate-50'"
                                        class="rounded-full px-4 py-2 text-xs font-semibold transition"
                                    >
                                        {{ option.label }}
                                    </button>
                                </template>
                            </div>

                            <div class="text-sm text-gray-500">
                                Menampilkan {{ filteredRecords.length }} catatan untuk <span class="font-semibold">{{ selectedModule === 'All' ? 'Semua Modul' : selectedModule }}</span>.
                            </div>

                            <div v-if="!filteredRecords.length" class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-6 text-sm text-gray-500">
                                Tidak ada data untuk filter ini.
                            </div>

                            <div v-else class="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-sm">
                                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                                    <thead class="bg-slate-50 text-xs uppercase tracking-[0.2em] text-gray-500">
                                        <tr>
                                            <th class="px-4 py-3">Modul</th>
                                            <th class="px-4 py-3">Deskripsi</th>
                                            <th class="px-4 py-3">Waktu</th>
                                            <th class="px-4 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template v-for="record in pagedRecords" :key="record.id">
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-4 font-semibold text-gray-900">{{ record.module }}</td>
                                                <td class="px-4 py-4 text-gray-600">{{ record.description }}</td>
                                                <td class="px-4 py-4 text-xs uppercase tracking-[0.15em] text-gray-400">{{ record.created_at }}</td>
                                                <td class="px-4 py-4 text-right">
                                                    <button
                                                        type="button"
                                                        class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-700 transition hover:bg-slate-50"
                                                        @click="toggleRowDetails(record.id)"
                                                    >
                                                        {{ isRowExpanded(record.id) ? 'Tutup' : 'Lihat Detail' }}
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="isRowExpanded(record.id)" class="bg-slate-50">
                                                <td colspan="4" class="px-4 py-4 text-sm text-gray-700">
                                                    <div class="space-y-4">
                                                        <div class="grid gap-3 md:grid-cols-3">
                                                            <div v-for="detail in record.details" :key="detail.label" class="rounded-2xl bg-white p-3 shadow-sm">
                                                                <p class="text-[11px] uppercase tracking-[0.2em] text-gray-400">{{ detail.label }}</p>
                                                                <p class="mt-2 text-sm text-gray-800">{{ detail.value }}</p>
                                                            </div>
                                                        </div>
                                                        <div v-if="record.photos && record.photos.length" class="space-y-3">
                                                            <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Foto Kegiatan</p>
                                                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                                <div v-for="photo in record.photos" :key="photo" class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                                                                    <img :src="photo" alt="Foto Go Action" class="h-40 w-full object-cover" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex flex-col gap-4 py-4 md:flex-row md:items-center md:justify-between">
                                <p class="text-sm text-gray-500">
                                    Menampilkan halaman {{ currentPage }} dari {{ pageCount }}.
                                </p>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded-full border px-4 py-2 text-sm font-semibold transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="!hasPreviousPage"
                                        @click="currentPage = Math.max(1, currentPage - 1)"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full border px-4 py-2 text-sm font-semibold transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="!hasNextPage"
                                        @click="currentPage = Math.min(pageCount, currentPage + 1)"
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Box: Penjelasan Konsep 5R dan Tiga Metode -->
                <div class="rounded-xl bg-white/95 p-10 shadow-xl shadow-blue-100/40 ring-1 ring-white/60">
                    <div class="mb-6 border-b border-blue-100 pb-4 text-center">
                        <h3 class="text-2xl font-semibold text-gray-900">Apa itu 5R dan bagaimana cara kita mencapainya?</h3>
                        <p class="mt-2 text-sm" style="color: #00529b;">Fondasi budaya kerja bersih, aman, dan selaras pada standar</p>
                    </div>
                    <div class="space-y-6 text-base leading-relaxed text-gray-700 text-center">
                        <p>
                            <span class="font-semibold" style="color: #00529b;">5R</span> adalah budaya kerja yang membangun lingkungan efektif, efisien, dan aman. Lima pilar utamanya dihadirkan sebagai kebiasaan harian yang mudah diingat dan diterapkan.
                        </p>

                        <div class="flex flex-wrap items-center justify-center gap-3 text-sm font-semibold uppercase tracking-wide" style="color: #00529b;">
                            <span class="rounded-full px-4 py-2 shadow-inner" style="background-color: rgba(0, 82, 155, 0.1);">Ringkas</span>
                            <span class="rounded-full px-4 py-2 shadow-inner" style="background-color: rgba(0, 82, 155, 0.1);">Rapi</span>
                            <span class="rounded-full px-4 py-2 shadow-inner" style="background-color: rgba(0, 82, 155, 0.1);">Resik</span>
                            <span class="rounded-full px-4 py-2 shadow-inner" style="background-color: rgba(0, 82, 155, 0.1);">Rawat</span>
                            <span class="rounded-full px-4 py-2 shadow-inner" style="background-color: rgba(0, 82, 155, 0.1);">Rajin</span>
                        </div>

                        <p>
                            Implementasinya dipercepat melalui tiga metode utama: <span class="font-semibold" style="color: #00529b;">GO ACTION</span> (aksi cepat memilah, menata, membuang, dan menertibkan), <span class="font-semibold" style="color: #00529b;">GO BOOST</span> (temuan dan observasi perbaikan), serta <span class="font-semibold" style="color: #00529b;">GO CARE</span> (perbaikan tuntas dan pemeliharaan berkelanjutan).
                        </p>

                        <p class="text-sm text-gray-500">
                            Ketiganya membentuk siklus peningkatan berkelanjutan yang sederhana namun kuat: <span class="font-semibold" style="color: #00529b;">temukan → tindak → rawat</span>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
