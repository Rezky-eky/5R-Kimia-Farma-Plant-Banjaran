<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MonthlyExcelExport from '@/Components/MonthlyExcelExport.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    topGoBoostCreators: { type: Array, default: () => [] },
    topGoSolvers: { type: Array, default: () => [] },
    topGoCares: { type: Array, default: () => [] },
    topGoCheckFinders: { type: Array, default: () => [] },
    topGoCheckClosers: { type: Array, default: () => [] },
    departementStats: { type: Array, default: () => [] },
    topUsersByPoints: { type: Array, default: () => [] },
    isAdmin: { type: Boolean, default: false },
    period: { type: Object, default: () => ({ start: '', end: '', label: 'Semua periode' }) },
});

const startMonth = ref(props.period.start || new Date().toISOString().slice(0, 7));
const endMonth = ref(props.period.end || startMonth.value);
const leaderboardRoute = props.isAdmin ? 'admin.go_reward' : 'leaderboard';
const reportRoute = props.isAdmin ? 'admin.reports.go_reward.export' : 'reports.go_reward.export';
const applyPeriod = () => router.get(route(leaderboardRoute), { start_month: startMonth.value, end_month: endMonth.value }, { preserveState: true, preserveScroll: true });
const rankClass = (index) => index === 0
    ? 'bg-[#ead2a7] text-[#765a45] ring-2 ring-[#f3dfbd]'
    : index === 1
        ? 'bg-[#dce5e4] text-[#5e706f]'
        : index === 2
            ? 'bg-[#efd0b6] text-[#765a45]'
            : 'bg-[#edf3f0] text-[#5f827f]';
</script>

<template>
    <Head title="Go Reward - Dashboard Pemenang" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                Go Reward – Dashboard Pemenang
            </h2>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div><h3 class="text-base font-semibold text-slate-800">Periode klasemen</h3><p class="mt-1 text-sm text-slate-500">{{ period.label }}</p></div>
                        <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="applyPeriod">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mulai<input v-model="startMonth" type="month" class="mt-1 block min-h-[42px] rounded-lg border-slate-200 text-sm shadow-sm" /></label>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Akhir<input v-model="endMonth" type="month" class="mt-1 block min-h-[42px] rounded-lg border-slate-200 text-sm shadow-sm" /></label>
                            <button type="submit" class="min-h-[42px] rounded-lg bg-[#789e98] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#668c86]">Terapkan</button>
                        </form>
                    </div>
                    <MonthlyExcelExport :export-route="reportRoute" />
                </div>

                <!-- 1. Bagian teraktif -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-800">Bagian Teraktif</h3>
                    <p class="text-sm text-gray-600 mb-4">Gabungan aktivitas GO ACTION, GO BOOST, dan GO CARE per bagian.</p>
                    </div><span class="rounded-full bg-[#edf3f0] px-3 py-1 text-xs font-semibold text-[#5f827f]">Klasemen bagian</span></div>
                    <div class="space-y-2">
                        <div
                            v-for="(stat, index) in departementStats"
                            :key="stat.bagian"
                            class="motion-lift flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-[#f8fbf9] p-3"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold" :class="rankClass(index)">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-medium text-gray-800">{{ stat.bagian }}</span>
                            </div>
                            <span class="text-right"><strong class="block font-semibold text-slate-800">{{ stat.total }} aktivitas</strong><small class="text-xs text-slate-400">Juara {{ index + 1 }}</small></span>
                        </div>
                        <p v-if="departementStats.length === 0" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>

                <!-- 2. Poin tertinggi -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-800 mb-1">Poin Tertinggi</h3>
                    <p class="text-sm text-gray-600 mb-4">Poin dari GO BOOST (booster + solver masing-masing 10 pt) dan GO CARE (10 pt per laporan).</p>
                    <div class="space-y-2">
                        <div
                            v-for="(item, index) in topUsersByPoints"
                            :key="item.user_id"
                            class="motion-lift flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-[#f8fbf9] p-3"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold" :class="rankClass(index)">
                                    {{ item.rank }}
                                </span>
                                <span class="font-medium text-gray-800">{{ item.name || 'N/A' }}</span>
                                <span class="text-sm text-gray-500">NPP: {{ item.npp || '-' }}</span>
                                <span class="text-xs text-slate-400">{{ item.bagian || 'Bagian belum tersedia' }}</span>
                            </div>
                            <span class="text-right"><strong class="block font-semibold text-slate-800">{{ item.points_balance }} pt</strong><small class="text-xs text-slate-400">Juara {{ item.rank }}</small></span>
                        </div>
                        <p v-if="topUsersByPoints.length === 0" class="text-sm text-gray-500 py-4">Belum ada data poin.</p>
                    </div>
                </div>

                <!-- 3. Go Boost terbanyak -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-800 mb-1">Pemenang Go Boost Finder</h3>
                    <p class="text-sm text-gray-600 mb-4">Karyawan dengan jumlah temuan GO BOOST terbanyak.</p>
                    </div><span class="rounded-full bg-[#f0dfe9] px-3 py-1 text-xs font-semibold text-[#806276]">Juara temuan</span></div>
                    <div class="space-y-2">
                        <div
                            v-for="(item, index) in topGoBoostCreators"
                            :key="item.user_id"
                            class="motion-lift flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-[#f8fbf9] p-3"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold" :class="rankClass(index)">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-medium text-gray-800">{{ item.name || 'N/A' }}</span>
                                <span class="text-xs text-slate-400">{{ item.bagian || 'Bagian belum tersedia' }} · NPP {{ item.npp || '-' }}</span>
                            </div>
                            <span class="text-right"><strong class="block font-semibold text-slate-800">{{ item.total }} temuan</strong><small class="text-xs text-slate-400">{{ item.points }} pt · Juara {{ index + 1 }}</small></span>
                        </div>
                        <p v-if="topGoBoostCreators.length === 0" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>

                <!-- 4. Solver terbanyak -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-800 mb-1">Pemenang Go Boost Closer</h3>
                    <p class="text-sm text-gray-600 mb-4">Karyawan yang menyelesaikan perbaikan GO BOOST terbanyak.</p>
                    </div><span class="rounded-full bg-[#dceae5] px-3 py-1 text-xs font-semibold text-[#527772]">Juara penyelesaian</span></div>
                    <div class="space-y-2">
                        <div
                            v-for="(item, index) in topGoSolvers"
                            :key="item.user_id"
                            class="motion-lift flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-[#f8fbf9] p-3"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold" :class="rankClass(index)">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-medium text-gray-800">{{ item.name || 'N/A' }}</span>
                                <span class="text-xs text-slate-400">{{ item.bagian || 'Bagian belum tersedia' }} · NPP {{ item.npp || '-' }}</span>
                            </div>
                            <span class="text-right"><strong class="block font-semibold text-slate-800">{{ item.total }} selesai</strong><small class="text-xs text-slate-400">{{ item.points }} pt · Juara {{ index + 1 }}</small></span>
                        </div>
                        <p v-if="topGoSolvers.length === 0" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>

                <!-- Go Check — Finder terbanyak -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-800 mb-1">Go Check Finder</h3>
                    <p class="text-sm text-gray-600 mb-4">Tim 5R dengan temuan audit terbanyak (sudah di-approve).</p>
                    <div class="space-y-2">
                        <div v-for="(item, index) in topGoCheckFinders" :key="item.user_id" class="motion-lift flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-[#f8fbf9] p-3">
                            <span class="min-w-0"><b class="mr-2 inline-flex h-7 w-7 items-center justify-center rounded-full text-xs" :class="rankClass(index)">{{ index + 1 }}</b><strong class="block truncate text-sm text-slate-700">{{ item.name || 'N/A' }}</strong><small class="ml-9 block text-xs text-slate-400">{{ item.bagian || 'Bagian belum tersedia' }} · NPP {{ item.npp || '-' }}</small></span>
                            <span class="shrink-0 text-right"><strong class="block text-sm text-slate-700">{{ item.total }} audit</strong><small class="text-xs text-slate-400">{{ item.points }} pt</small></span>
                        </div>
                        <p v-if="!topGoCheckFinders.length" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>

                <!-- Go Check — Closer (Solver) terbanyak -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-800 mb-1">Go Check Closer</h3>
                    <p class="text-sm text-gray-600 mb-4">Karyawan bagian dengan penyelesaian (solver) terbanyak yang di-approve.</p>
                    <div class="space-y-2">
                        <div v-for="(item, index) in topGoCheckClosers" :key="item.user_id" class="motion-lift flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-[#f8fbf9] p-3">
                            <span class="min-w-0"><b class="mr-2 inline-flex h-7 w-7 items-center justify-center rounded-full text-xs" :class="rankClass(index)">{{ index + 1 }}</b><strong class="block truncate text-sm text-slate-700">{{ item.name || 'N/A' }}</strong><small class="ml-9 block text-xs text-slate-400">{{ item.bagian || 'Bagian belum tersedia' }} · NPP {{ item.npp || '-' }}</small></span>
                            <span class="shrink-0 text-right"><strong class="block text-sm text-slate-700">{{ item.total }} close</strong><small class="text-xs text-slate-400">{{ item.points }} pt</small></span>
                        </div>
                        <p v-if="!topGoCheckClosers.length" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>

                <!-- 5. Go Care terbanyak -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-800 mb-1">Pemenang Go Care</h3>
                    <p class="text-sm text-gray-600 mb-4">Karyawan dengan poin GO CARE terbanyak (hanya laporan disetujui, 10 pt per approval).</p>
                    <div class="space-y-2">
                        <div
                            v-for="(item, index) in topGoCares"
                            :key="item.user_id"
                            class="motion-lift flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-[#f8fbf9] p-3"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold" :class="rankClass(index)">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-medium text-gray-800">{{ item.name || 'N/A' }}</span>
                                <span class="text-xs text-slate-400">{{ item.bagian || 'Bagian belum tersedia' }} · NPP {{ item.npp || '-' }}</span>
                            </div>
                            <span class="text-right"><strong class="block font-semibold text-slate-800">{{ item.total }} pt</strong><small class="text-xs text-slate-400">Juara {{ index + 1 }}</small></span>
                        </div>
                        <p v-if="topGoCares.length === 0" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
