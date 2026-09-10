<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, default: () => ({}) },
    teamsMigrationPending: { type: Boolean, default: false },
    leaderboards: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="Manajemen Go Check" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold text-gray-900">Manajemen Go Check (5R)</h2>
                <BackToDashboard />
            </div>
        </template>

        <div class="py-10 max-w-5xl mx-auto px-4 space-y-6">
            <p class="text-sm text-gray-600">
                Panel Ketua/Sekretaris/Admin — kelola tim 5R, penugasan bagian, approve/reject temuan audit.
            </p>

            <div
                v-if="teamsMigrationPending"
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
            >
                <p class="font-semibold">Tabel tim 5R belum tersedia di database.</p>
                <p class="mt-1">Jalankan migrasi di terminal project: <code class="rounded bg-amber-100 px-1.5 py-0.5 text-xs">php artisan migrate</code></p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="rounded-xl bg-slate-50 p-5 ring-1 ring-slate-200">
                    <p class="text-xs text-slate-600 uppercase font-semibold">Total temuan</p>
                    <p class="text-3xl font-bold text-slate-900 mt-1">{{ stats.total ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-blue-50 p-5 ring-1 ring-blue-100">
                    <p class="text-xs text-blue-800 uppercase font-semibold">Menunggu Solver</p>
                    <p class="text-3xl font-bold text-blue-900 mt-1">{{ stats.waiting_solver ?? 0 }}</p>
                    <p class="text-[10px] text-blue-700 mt-1">Finder sudah input, bagian belum perbaiki</p>
                </div>
                <div class="rounded-xl bg-amber-50 p-5 ring-1 ring-amber-100">
                    <p class="text-xs text-amber-800 uppercase font-semibold">Siap di-approve</p>
                    <p class="text-3xl font-bold text-amber-900 mt-1">{{ stats.pending ?? 0 }}</p>
                    <p class="text-[10px] text-amber-700 mt-1">Solver selesai, tunggu manajemen</p>
                </div>
                <div class="rounded-xl bg-green-50 p-5 ring-1 ring-green-100">
                    <p class="text-xs text-green-800 uppercase font-semibold">Sudah approve</p>
                    <p class="text-3xl font-bold text-green-900 mt-1">{{ stats.approved ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-violet-50 p-5 ring-1 ring-violet-100">
                    <p class="text-xs text-violet-800 uppercase font-semibold">Anggota tim 5R</p>
                    <p class="text-3xl font-bold text-violet-900 mt-1">{{ stats.team_count ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-teal-50 p-5 ring-1 ring-teal-100">
                    <p class="text-xs text-teal-800 uppercase font-semibold">Jadwal aktif</p>
                    <p class="text-3xl font-bold text-teal-900 mt-1">{{ stats.upcoming_schedules ?? 0 }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <Link
                    :href="route('go_check.management.team')"
                    class="rounded-xl bg-[#00529b] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#004080]"
                >
                    Kelola Tim, Penugasan & Jadwal
                </Link>
                <Link
                    :href="route('go_check.management.index')"
                    class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-[#00529b] ring-1 ring-[#00529b]/30 hover:bg-slate-50"
                >
                    Data Go Check (Approve/Reject)
                </Link>
                <BackToDashboard v-if="$page.props.auth?.user?.role === 'admin'" admin />
            </div>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-end justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-800">Go Reward</h3><p class="mt-1 text-sm text-slate-500">Klasemen lengkap untuk tim 5R.</p></div><Link :href="route('leaderboard')" class="text-sm font-semibold text-[#4b7892]">Lihat semua →</Link></div>
                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div v-for="board in [{ title: 'Go Boost Finder', items: leaderboards.topGoBoostCreators }, { title: 'Go Boost Closer', items: leaderboards.topGoSolvers }, { title: 'Go Care', items: leaderboards.topGoCares }, { title: 'Go Check Finder', items: leaderboards.topGoCheckFinders }, { title: 'Go Check Closer', items: leaderboards.topGoCheckClosers }, { title: 'Poin Tertinggi', items: leaderboards.topUsersByPoints }]" :key="board.title" class="rounded-lg bg-[#f4f8fb] p-3">
                        <h4 class="text-sm font-semibold text-slate-700">{{ board.title }}</h4>
                        <div class="mt-2 max-h-48 space-y-1.5 overflow-y-auto"><div v-for="(item, index) in board.items" :key="`${board.title}-${item.user_id}`" class="flex justify-between gap-2 rounded bg-white px-2 py-1.5 text-xs"><span class="min-w-0 truncate"><b class="mr-1 text-[#4b7892]">{{ index + 1 }}</b>{{ item.name || 'N/A' }}<small class="block pl-4 text-slate-400">{{ item.bagian || '-' }}</small></span><strong class="shrink-0 text-[#4b7892]">{{ item.points ?? item.points_balance ?? item.total }} pt</strong></div></div>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
