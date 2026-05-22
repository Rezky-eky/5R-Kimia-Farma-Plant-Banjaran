<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    topGoBoostCreators: { type: Array, default: () => [] },
    topGoSolvers: { type: Array, default: () => [] },
    topGoCares: { type: Array, default: () => [] },
    topGoCheckFinders: { type: Array, default: () => [] },
    topGoCheckClosers: { type: Array, default: () => [] },
    departementStats: { type: Array, default: () => [] },
    topUsersByPoints: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Go Reward - Dashboard Pemenang" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                Go Reward – Dashboard Pemenang
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-10">
                <!-- 1. Bagian teraktif -->
                <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Bagian Teraktif</h3>
                    <p class="text-sm text-gray-600 mb-4">Gabungan aktivitas GO ACTION, GO BOOST, dan GO CARE per bagian.</p>
                    <div class="space-y-2">
                        <div
                            v-for="(stat, index) in departementStats"
                            :key="stat.bagian"
                            class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full font-semibold text-sm"
                                    :class="index === 0 ? 'bg-amber-100 text-amber-800' : index === 1 ? 'bg-gray-200 text-gray-700' : index === 2 ? 'bg-amber-200/70 text-amber-900' : 'bg-blue-100 text-blue-700'">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-medium text-gray-800">{{ stat.bagian }}</span>
                            </div>
                            <span class="font-semibold text-gray-900">{{ stat.total }} aktivitas</span>
                        </div>
                        <p v-if="departementStats.length === 0" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>

                <!-- 2. Poin tertinggi -->
                <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Poin Tertinggi</h3>
                    <p class="text-sm text-gray-600 mb-4">Poin dari GO BOOST (booster + solver masing-masing 10 pt) dan GO CARE (10 pt per laporan).</p>
                    <div class="space-y-2">
                        <div
                            v-for="(item, index) in topUsersByPoints"
                            :key="item.user_id"
                            class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full font-semibold text-sm"
                                    :class="index === 0 ? 'bg-amber-100 text-amber-800' : index === 1 ? 'bg-gray-200 text-gray-700' : index === 2 ? 'bg-amber-200/70 text-amber-900' : 'bg-indigo-100 text-indigo-700'">
                                    {{ item.rank }}
                                </span>
                                <span class="font-medium text-gray-800">{{ item.name || 'N/A' }}</span>
                                <span class="text-sm text-gray-500">NPP: {{ item.npp || '-' }}</span>
                                <span v-if="item.bagian" class="text-xs text-gray-400">{{ item.bagian }}</span>
                            </div>
                            <span class="font-semibold text-gray-900">{{ item.points_balance }} pt</span>
                        </div>
                        <p v-if="topUsersByPoints.length === 0" class="text-sm text-gray-500 py-4">Belum ada data poin.</p>
                    </div>
                </div>

                <!-- 3. Go Boost terbanyak -->
                <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pemenang Go Boost Terbanyak (Temuan)</h3>
                    <p class="text-sm text-gray-600 mb-4">Karyawan dengan jumlah temuan GO BOOST terbanyak.</p>
                    <div class="space-y-2">
                        <div
                            v-for="(item, index) in topGoBoostCreators"
                            :key="item.user_id"
                            class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full font-semibold text-sm"
                                    :class="index === 0 ? 'bg-amber-100 text-amber-800' : index === 1 ? 'bg-gray-200 text-gray-700' : index === 2 ? 'bg-amber-200/70 text-amber-900' : 'bg-blue-100 text-blue-700'">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-medium text-gray-800">{{ item.name || 'N/A' }}</span>
                                <span class="text-sm text-gray-500">NPP: {{ item.npp || '-' }}</span>
                            </div>
                            <span class="font-semibold text-gray-900">{{ item.total }} temuan</span>
                        </div>
                        <p v-if="topGoBoostCreators.length === 0" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>

                <!-- 4. Solver terbanyak -->
                <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pemenang Go Solver Terbanyak (Perbaikan)</h3>
                    <p class="text-sm text-gray-600 mb-4">Karyawan yang menyelesaikan perbaikan GO BOOST terbanyak.</p>
                    <div class="space-y-2">
                        <div
                            v-for="(item, index) in topGoSolvers"
                            :key="item.user_id"
                            class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full font-semibold text-sm"
                                    :class="index === 0 ? 'bg-amber-100 text-amber-800' : index === 1 ? 'bg-gray-200 text-gray-700' : index === 2 ? 'bg-amber-200/70 text-amber-900' : 'bg-blue-100 text-blue-700'">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-medium text-gray-800">{{ item.name || 'N/A' }}</span>
                                <span class="text-sm text-gray-500">NPP: {{ item.npp || '-' }}</span>
                            </div>
                            <span class="font-semibold text-gray-900">{{ item.total }} perbaikan</span>
                        </div>
                        <p v-if="topGoSolvers.length === 0" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>

                <!-- Go Check — Finder terbanyak -->
                <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Go Check — Finder Terbanyak</h3>
                    <p class="text-sm text-gray-600 mb-4">Tim 5R dengan temuan audit terbanyak (sudah di-approve).</p>
                    <div class="space-y-2">
                        <div v-for="(item, index) in topGoCheckFinders" :key="item.user_id" class="flex justify-between p-3 rounded-xl bg-gray-50">
                            <span class="font-medium">{{ index + 1 }}. {{ item.name }} ({{ item.npp }})</span>
                            <span class="font-semibold">{{ item.total }} audit</span>
                        </div>
                        <p v-if="!topGoCheckFinders.length" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>

                <!-- Go Check — Closer (Solver) terbanyak -->
                <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Go Check — Closer Terbanyak</h3>
                    <p class="text-sm text-gray-600 mb-4">Karyawan bagian dengan penyelesaian (solver) terbanyak yang di-approve.</p>
                    <div class="space-y-2">
                        <div v-for="(item, index) in topGoCheckClosers" :key="item.user_id" class="flex justify-between p-3 rounded-xl bg-gray-50">
                            <span class="font-medium">{{ index + 1 }}. {{ item.name }} ({{ item.npp }})</span>
                            <span class="font-semibold">{{ item.total }} close</span>
                        </div>
                        <p v-if="!topGoCheckClosers.length" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>

                <!-- 5. Go Care terbanyak -->
                <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pemenang Go Care Terbanyak</h3>
                    <p class="text-sm text-gray-600 mb-4">Karyawan dengan jumlah laporan GO CARE terbanyak.</p>
                    <div class="space-y-2">
                        <div
                            v-for="(item, index) in topGoCares"
                            :key="item.user_id"
                            class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full font-semibold text-sm"
                                    :class="index === 0 ? 'bg-amber-100 text-amber-800' : index === 1 ? 'bg-gray-200 text-gray-700' : index === 2 ? 'bg-amber-200/70 text-amber-900' : 'bg-blue-100 text-blue-700'">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-medium text-gray-800">{{ item.name || 'N/A' }}</span>
                                <span class="text-sm text-gray-500">NPP: {{ item.npp || '-' }}</span>
                            </div>
                            <span class="font-semibold text-gray-900">{{ item.total }} laporan</span>
                        </div>
                        <p v-if="topGoCares.length === 0" class="text-sm text-gray-500 py-4">Belum ada data.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
