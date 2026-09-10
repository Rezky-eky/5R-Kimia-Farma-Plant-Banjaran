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
    leaderboards: {
        type: Object,
        default: () => ({}),
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
const activityBars = computed(() => [
    { label: 'Go Action', value: props.stats.total_go_actions ?? 0, color: '#a9c8c0' },
    { label: 'Go Boost', value: props.stats.total_go_boosts ?? 0, color: '#d8b7c8' },
    { label: 'Go Care', value: props.stats.total_go_cares ?? 0, color: '#e8c7a8' },
    { label: 'Barang Ringkas', value: props.stats.total_dbr_items ?? 0, color: '#b9c7dc' },
    { label: 'Go Check', value: props.stats.total_go_checks ?? 0, color: '#c3c9a8' },
]);
const activityMax = computed(() => Math.max(1, ...activityBars.value.map((item) => item.value)));

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

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Quick Actions — 2 baris, 4 kolom -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <Link
                        :href="route('admin.audit.index')"
                        class="motion-lift inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-[#b9cbd4] px-3 py-3 text-sm font-semibold text-[#405765] shadow-sm text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="leading-tight">Laporan 5R Keseluruhan</span>
                    </Link>
                    <Link
                        v-if="isAdmin"
                        :href="route('admin.go_reward')"
                        class="motion-lift inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-[#ead2a7] px-3 py-3 text-sm font-semibold text-[#6c5733] shadow-sm text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        Go Reward
                    </Link>
                    <Link
                        :href="route('admin.go_action.index')"
                        class="motion-lift inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-[#aecbc4] px-3 py-3 text-sm font-semibold text-[#3f625d] shadow-sm text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Data Go Action
                    </Link>
                    <Link
                        :href="route('admin.go_boost.index')"
                        class="motion-lift inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-[#d7bbd2] px-3 py-3 text-sm font-semibold text-[#684f64] shadow-sm text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        Data Go Boost
                    </Link>
                    <Link
                        :href="route('admin.go_care.index')"
                        class="motion-lift inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-[#e6b9b4] px-3 py-3 text-sm font-semibold text-[#754c4a] shadow-sm text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        Data Go Care
                    </Link>
                    <Link
                        :href="route('go_check.management.dashboard')"
                        class="motion-lift inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-[#b9c9aa] px-3 py-3 text-sm font-semibold text-[#566343] shadow-sm text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        Kelola Go Check
                    </Link>
                    <Link
                        :href="route('go_offer.index')"
                        class="motion-lift inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-[#c2d2e1] px-3 py-3 text-sm font-semibold text-[#4d6172] shadow-sm text-center"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                        </svg>
                        Go Offer
                    </Link>
                    <Link
                        :href="route('go_sale.index')"
                        class="motion-lift inline-flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-[#efd0b6] px-3 py-3 text-sm font-semibold text-[#765a45] shadow-sm text-center"
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
                        class="rounded-xl border border-blue-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md cursor-pointer block"
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
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md cursor-pointer block"
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
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md cursor-pointer block"
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
                        class="rounded-xl border border-teal-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md cursor-pointer block"
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
                        class="rounded-xl border border-blue-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md cursor-pointer block"
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
                        class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md cursor-pointer block"
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

                <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <section class="soft-panel rounded-xl p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Komposisi Aktivitas 5R</h3>
                                <p class="mt-1 text-sm text-slate-500">Jumlah data setiap fitur utama.</p>
                            </div>
                            <span class="rounded-full bg-[#edf3f0] px-3 py-1 text-xs font-semibold text-[#5f827f]">Total</span>
                        </div>
                        <div class="mt-6 flex items-center justify-center">
                            <div class="relative flex h-44 w-44 items-center justify-center rounded-full" style="background: conic-gradient(#a9c8c0 0 20%, #d8b7c8 20% 40%, #e8c7a8 40% 60%, #b9c7dc 60% 80%, #c3c9a8 80% 100%);">
                                <div class="flex h-28 w-28 flex-col items-center justify-center rounded-full bg-[#fffdfa] shadow-inner">
                                    <span class="text-2xl font-bold text-slate-700">{{ stats.total_laporan_keseluruhan }}</span>
                                    <span class="text-[11px] uppercase tracking-wide text-slate-400">laporan</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-5 xl:grid-cols-2">
                            <div v-for="item in activityBars" :key="item.label" class="flex items-center gap-2 text-xs text-slate-600">
                                <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: item.color }"></span>
                                <span class="truncate">{{ item.label }}</span>
                                <strong class="ml-auto text-slate-800">{{ item.value }}</strong>
                            </div>
                        </div>
                    </section>

                    <section class="soft-panel rounded-xl p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Grafik Aktivitas</h3>
                                <p class="mt-1 text-sm text-slate-500">Perbandingan jumlah data tiap fitur.</p>
                            </div>
                            <span class="rounded-full bg-[#f8eee8] px-3 py-1 text-xs font-semibold text-[#9a725a]">Ringkasan</span>
                        </div>
                        <div class="mt-6 space-y-4">
                            <div v-for="item in activityBars" :key="`bar-${item.label}`">
                                <div class="mb-1.5 flex justify-between text-xs font-medium text-slate-600"><span>{{ item.label }}</span><span>{{ item.value }}</span></div>
                                <div class="h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full transition-all duration-700 ease-out" :style="{ width: `${Math.max(4, (item.value / activityMax) * 100)}%`, backgroundColor: item.color }"></div></div>
                            </div>
                        </div>
                    </section>

                    <section class="soft-panel rounded-xl p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div><h3 class="text-lg font-semibold text-slate-800">Sorotan Juara 5R</h3><p class="mt-1 text-sm text-slate-500">Tiga klasemen teratas dari periode berjalan.</p></div>
                            <span class="rounded-full bg-[#f7eee8] px-3 py-1 text-xs font-semibold text-[#9a725a]">Top 3</span>
                        </div>
                        <div class="mt-5 space-y-3">
                            <div v-for="board in [{ title: 'Go Boost Finder', items: leaderboards.topGoBoostCreators }, { title: 'Go Boost Closer', items: leaderboards.topGoSolvers }, { title: 'Go Care', items: leaderboards.topGoCares }]" :key="`winner-${board.title}`">
                                <div class="mb-2 flex items-center justify-between"><span class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ board.title }}</span><span class="text-xs text-slate-400">Juara 1</span></div>
                                <div v-if="board.items?.length" class="rounded-lg border border-[#e5ebe7] bg-[#f8fbf9] p-3">
                                    <div class="flex items-center justify-between gap-3"><div class="min-w-0"><p class="truncate font-semibold text-slate-700">{{ board.items[0].name || 'N/A' }}</p><p class="truncate text-xs text-slate-500">{{ board.items[0].bagian || 'Bagian belum tersedia' }}</p><p class="truncate text-[11px] text-slate-400">{{ board.items[0].npp || 'NPP -' }}</p></div><strong class="shrink-0 rounded-full bg-[#dceae5] px-3 py-1 text-sm text-[#527772]">{{ board.items[0].points ?? board.items[0].total }} pt</strong></div>
                                </div>
                                <p v-else class="text-xs text-slate-400">Belum ada data pemenang.</p>
                            </div>
                        </div>
                        <Link :href="route('leaderboard')" class="mt-5 inline-flex w-full items-center justify-center rounded-lg bg-[#789e98] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#668c86] hover:shadow-md">Buka leaderboard lengkap</Link>
                    </section>
                </div>

                <section v-if="leaderboards.topGoBoostCreators?.length || leaderboards.topGoCares?.length" class="soft-panel rounded-xl p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div><h3 class="text-lg font-semibold text-slate-800">Leaderboard 5R</h3><p class="mt-1 text-sm text-slate-500">Pemenang aktivitas utama yang dapat dilihat semua role.</p></div>
                        <Link :href="route('admin.go_reward')" class="text-sm font-semibold text-[#648b84] hover:text-[#4f726c]">Lihat klasemen lengkap →</Link>
                    </div>
                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div v-for="board in [{ title: 'Go Boost Finder', items: leaderboards.topGoBoostCreators }, { title: 'Go Boost Closer', items: leaderboards.topGoSolvers }, { title: 'Go Care', items: leaderboards.topGoCares }, { title: 'Go Check Finder', items: leaderboards.topGoCheckFinders }, { title: 'Go Check Closer', items: leaderboards.topGoCheckClosers }, { title: 'Poin Tertinggi', items: leaderboards.topUsersByPoints }]" :key="board.title" class="rounded-lg bg-[#f7faf8] p-4">
                            <h4 class="text-sm font-semibold text-slate-700">{{ board.title }}</h4>
                            <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1"><div v-for="(item, index) in board.items" :key="`${board.title}-${item.user_id}`" class="flex items-start justify-between gap-2 rounded-md bg-white px-2.5 py-2 text-xs shadow-sm"><span class="min-w-0"><b class="mr-1.5 text-[#4b7892]">{{ index + 1 }}</b><strong class="block truncate text-slate-700">{{ item.name || 'N/A' }}</strong><small class="ml-4 block truncate text-slate-400">{{ item.bagian || 'Bagian belum tersedia' }}</small></span><span class="shrink-0 text-right font-semibold text-[#4b7892]">{{ item.points ?? item.points_balance ?? item.total }}<small class="block font-normal text-slate-400">pt</small></span></div></div>
                            <p v-if="!board.items.length" class="mt-3 text-xs text-slate-400">Belum ada data.</p>
                        </div>
                    </div>
                </section>

                <!-- Tren Kinerja -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
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
                <div v-if="departementStats && departementStats.length > 0" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
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

