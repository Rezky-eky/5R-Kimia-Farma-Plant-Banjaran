<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    month: { type: String, required: true },
    rows: { type: Array, default: () => [] },
});

const form = useForm({
    month: props.month,
});

const apply = () => {
    router.get(route('admin.go_action.weekly_realization'), { month: form.month }, { preserveState: true, preserveScroll: true });
};

const exportExcel = () => {
    const url = route('admin.go_action.weekly_realization_export', { month: form.month });
    window.location.href = url;
};
</script>

<template>
    <Head title="Realisasi Mingguan GO ACTION" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Realisasi Mingguan GO ACTION
                </h2>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('admin.go_action.index')"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Data GO ACTION
                    </Link>
                    <BackToDashboard admin />
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6 rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div class="w-full md:w-72">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Bulan
                            </label>
                            <input
                                type="month"
                                v-model="form.month"
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0"
                            />
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="apply"
                                class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-300/50 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
                            >
                                Tampilkan
                            </button>
                            <button
                                type="button"
                                @click="exportExcel"
                                class="rounded-xl bg-[#00529b] px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-[#004080] focus:outline-none focus:ring-2 focus:ring-[#00529b] focus:ring-offset-2"
                            >
                                Export Excel
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white/90 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nama Bagian</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Week 1</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Week 2</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Week 3</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Week 4</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="row in rows" :key="row.bagian" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ row.no }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ row.bagian }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-900">
                                        <input type="checkbox" :checked="row.week1" disabled />
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-900">
                                        <input type="checkbox" :checked="row.week2" disabled />
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-900">
                                        <input type="checkbox" :checked="row.week3" disabled />
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-900">
                                        <input type="checkbox" :checked="row.week4" disabled />
                                    </td>
                                </tr>
                                <tr v-if="rows.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                        Tidak ada data GO ACTION di bulan ini.
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

