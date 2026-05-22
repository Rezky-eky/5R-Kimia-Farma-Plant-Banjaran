<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="Manajemen Go Check" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-gray-900">Manajemen Go Check (5R)</h2>
        </template>

        <div class="py-10 max-w-5xl mx-auto px-4 space-y-6">
            <p class="text-sm text-gray-600">
                Panel Ketua/Sekretaris/Admin — kelola tim 5R, penugasan bagian, approve/reject temuan audit.
            </p>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
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
            </div>

            <div class="flex flex-wrap gap-3">
                <Link
                    :href="route('go_check.management.team')"
                    class="rounded-xl bg-[#00529b] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#004080]"
                >
                    Kelola Tim & Penugasan Bagian
                </Link>
                <Link
                    :href="route('go_check.management.index')"
                    class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-[#00529b] ring-1 ring-[#00529b]/30 hover:bg-slate-50"
                >
                    Data Go Check (Approve/Reject)
                </Link>
                <Link
                    v-if="$page.props.auth?.user?.role === 'admin'"
                    :href="route('admin.dashboard')"
                    class="rounded-xl bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-200"
                >
                    Admin Dashboard
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
