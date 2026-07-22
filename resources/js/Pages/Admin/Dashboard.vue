<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    trendData: {
        type: Array,
        default: () => [],
    },
    departementStats: {
        type: Array,
        default: () => [],
    }, 
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const fallbackTrendData = [
    { bulan: 'Jan', open: 12, closed: 7 },
    { bulan: 'Feb', open: 15, closed: 9 },
    { bulan: 'Mar', open: 11, closed: 13 },
    { bulan: 'Apr', open: 18, closed: 14 },
    { bulan: 'Mei', open: 16, closed: 17 },
    { bulan: 'Jun', open: 14, closed: 19 },
];

const chartData = computed(() =>
    props.trendData && props.trendData.length
        ? props.trendData
        : fallbackTrendData,
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
    <Head title="Data 5R - 5R Kimia Farma Plant Banjaran" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                {{ isAdmin ? 'Admin Dashboard' : 'Data 5R' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-10">
                <!-- Quick Actions — 2 baris, 4 kolom -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <Link
                        :href="route('admin.audit.index')"
                        class="inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-blue-600 px-3 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-300/50 transition hover:bg-blue-700 text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="leading-tight">Laporan 5R Keseluruhan</span>
                    </Link>
                    <Link
                        v-if="isAdmin"
                        :href="route('admin.go_reward')"
                        class="inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-amber-600 px-3 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-300/50 transition hover:bg-amber-700 text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        Go Reward
                    </Link>
                    <Link
                        :href="route('admin.go_action.index')"
                        class="inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-white shadow-lg transition text-center"
                        style="background-color: #00529b;"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Data Go Action
                    </Link>
                    <Link
                        :href="route('admin.go_boost.index')"
                        class="inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-purple-600 px-3 py-3 text-sm font-semibold text-white shadow-lg shadow-purple-300/50 transition hover:bg-purple-700 text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        Data Go Boost
                    </Link>
                    <Link
                        :href="route('admin.go_care.index')"
                        class="inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-rose-600 px-3 py-3 text-sm font-semibold text-white shadow-lg shadow-rose-300/50 transition hover:bg-rose-700 text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        Data Go Care
                    </Link>
                    <Link
                        :href="route('go_check.management.dashboard')"
                        class="inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-teal-700 px-3 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-teal-800 text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        Kelola Go Check
                    </Link>
                    <Link
                        :href="route('go_offer.index')"
                        class="inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-white shadow-lg transition text-center"
                        style="background-color: #00529b;"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                        </svg>
                        Go Offer
                    </Link>
                    <Link
                        :href="route('go_sale.index')"
                        class="inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-white shadow-lg transition text-center"
                        style="background-color: #00529b;"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Go Sale
                    </Link>
                </div>

                <!-- Statistik Cards (clickable) — 2–3 kolom agar rapi -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Total GO ACTION -->
                    <Link
                        :href="route('admin.go_action.index')"
                        class="rounded-2xl bg-gradient-to-br from-blue-50 via-white to-blue-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60 cursor-pointer block"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Laporan</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">GO ACTION</h4>
                            </div>
                            <span class="text-xl">🚀</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-blue-600">{{ stats.total_go_actions }}</span>
                            <span class="text-xs text-gray-500">laporan</span>
                        </div>
                        <p class="mt-2 text-xs text-blue-600 font-medium">Klik untuk detail →</p>
                    </Link>

                    <!-- Total GO BOOST (Temuan) -->
                    <Link
                        :href="route('admin.go_boost.index')"
                        class="rounded-2xl bg-gradient-to-br from-purple-50 via-white to-purple-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60 cursor-pointer block"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Temuan</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">GO BOOST</h4>
                            </div>
                            <span class="text-xl">⚡</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-purple-600">{{ stats.total_go_boosts }}</span>
                            <span class="text-xs text-gray-500">temuan</span>
                        </div>
                        <p class="mt-2 text-xs text-purple-600 font-medium">Klik untuk detail →</p>
                    </Link>

                    <!-- Total GO CARE (Perbaikan) -->
                    <Link
                        :href="route('admin.go_care.index')"
                        class="rounded-2xl bg-gradient-to-br from-slate-50 via-white to-slate-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60 cursor-pointer block"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Perbaikan</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">GO CARE</h4>
                            </div>
                            <span class="text-xl">💛</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold" style="color: #00529b;">{{ stats.total_go_cares }}</span>
                            <span class="text-xs text-gray-500">perbaikan</span>
                        </div>
                        <p class="mt-2 text-xs font-medium" style="color: #00529b;">Klik untuk detail →</p>
                    </Link>

                    <!-- Total Laporan 5R Keseluruhan -->
                    <Link
                        :href="route('admin.audit.index')"
                        class="rounded-2xl bg-gradient-to-br from-teal-50 via-white to-teal-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60 cursor-pointer block"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Semua Jenis</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Laporan 5R Keseluruhan</h4>
                            </div>
                            <span class="text-xl">📋</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-teal-700">{{ stats.total_laporan_keseluruhan }}</span>
                            <span class="text-xs text-gray-500">laporan</span>
                        </div>
                        <p class="mt-2 text-xs font-medium text-teal-700">Klik untuk detail →</p>
                    </Link>

                    <Link
                        v-if="isAdmin"
                        :href="route('admin.audit.index', { status: 'audited' })"
                        class="rounded-2xl bg-gradient-to-br from-blue-50 via-white to-blue-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60 cursor-pointer block"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Sudah diproses</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Audited</h4>
                            </div>
                            <span class="text-xl">✅</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold" style="color: #00529b;">{{ stats.total_audited }}</span>
                            <span class="text-xs text-gray-500">laporan</span>
                        </div>
                        <p class="mt-2 text-xs font-medium" style="color: #00529b;">Klik untuk detail →</p>
                    </Link>

                    <!-- Menunggu Audit -->
                    <Link
                        v-if="isAdmin"
                        :href="route('admin.audit.index', { status: 'pending' })"
                        class="rounded-2xl bg-gradient-to-br from-amber-50 via-white to-amber-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60 cursor-pointer block"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Menunggu audit/approve</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Pending</h4>
                            </div>
                            <span class="text-xl">⏳</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-amber-600">{{ stats.total_pending }}</span>
                            <span class="text-xs text-gray-500">laporan</span>
                        </div>
                        <p class="mt-2 text-xs text-amber-600 font-medium">Klik untuk detail →</p>
                    </Link>
                </div>

                <!-- Tren Kinerja -->
                <div class="rounded-2xl bg-white/90 p-6 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Tren Kinerja 5R (6 Bulan Terakhir)</h3>
                            <p class="text-sm text-gray-500">Perbandingan jumlah laporan PENDING dan AUDITED.</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                                <span class="h-2 w-6 rounded-full bg-rose-300"></span>
                                PENDING
                            </div>
                            <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                                <span class="h-2 w-6 rounded-full bg-blue-500"></span>
                                AUDITED
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

                <!-- Statistik Per Departemen -->
                <div v-if="departementStats && departementStats.length > 0" class="rounded-2xl bg-white/90 p-6 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 10 Departemen Berdasarkan Laporan</h3>
                    <div class="space-y-3">
                        <div
                            v-for="(stat, index) in departementStats"
                            :key="stat.bagian"
                            class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 font-semibold text-sm">
                                    {{ index + 1 }}
                                </span>
                                <span class="text-sm font-medium text-gray-700">{{ stat.bagian }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ stat.total }} laporan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

