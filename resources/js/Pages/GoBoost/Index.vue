<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    goBoosts: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Daftar GO BOOST" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Daftar GO BOOST
                </h2>
                <div class="flex flex-wrap items-center gap-2">
                    <BackToDashboard />
                    <Link
                        :href="route('go_boost.create')"
                        class="inline-flex items-center rounded-xl bg-[#00529b] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200/70 transition hover:bg-[#004080] focus:outline-none focus:ring-2 focus:ring-[#00529b] focus:ring-offset-2"
                    >
                        + Tambah Temuan Baru
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white/90 shadow-2xl shadow-blue-100/60 ring-1 ring-white/60 backdrop-blur">
                    <div class="px-8 py-6 border-b border-white/60">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Semua Temuan GO BOOST
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Daftar lengkap temuan ketidaksesuaian 5R yang telah dilaporkan
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-blue-100">
                            <thead class="bg-blue-50/70">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-blue-700"
                                    >
                                        No
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-blue-700"
                                    >
                                        Area Temuan
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-blue-700"
                                    >
                                        Ruangan
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-blue-700"
                                    >
                                        Penjelasan
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-blue-700"
                                    >
                                        PIC Terkait
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-blue-700"
                                    >
                                        Status
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-blue-700"
                                    >
                                        Tanggal
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-widest text-blue-700"
                                    >
                                        Pelapor
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white/95 divide-y divide-blue-50">
                                <tr
                                    v-for="(goBoost, index) in goBoosts"
                                    :key="goBoost.id"
                                    class="transition-colors hover:bg-blue-50/40"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                        {{ goBoost.area_temuan }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ goBoost.ruangan_temuan }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                                        {{ goBoost.penjelasan_temuan }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ goBoost.pic_terkait }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="goBoost.status === 'OPEN'"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700"
                                        >
                                            OPEN
                                        </span>
                                        <span
                                            v-else-if="goBoost.status === 'CLOSED'"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700"
                                        >
                                            CLOSED
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700"
                                        >
                                            {{ goBoost.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ goBoost.created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ goBoost.user_name }}
                                    </td>
                                </tr>
                                <tr v-if="goBoosts.length === 0">
                                    <td
                                        colspan="8"
                                        class="px-6 py-10 text-center text-sm text-gray-500"
                                    >
                                        Belum ada data temuan GO BOOST. 
                                        <Link
                                            :href="route('go_boost.create')"
                                            class="font-semibold text-[#00529b] hover:text-[#004080]"
                                        >
                                            Tambah temuan baru
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

