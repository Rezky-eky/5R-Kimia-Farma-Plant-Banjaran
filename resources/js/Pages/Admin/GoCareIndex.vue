<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import PhotoGallery from '@/Components/PhotoGallery.vue';
<<<<<<< HEAD
import MonthlyExcelExport from '@/Components/MonthlyExcelExport.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
=======
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
>>>>>>> 2c0a385462210724212168efee04285568c04831

const props = defineProps({
    goCares: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            approval_status: '',
        }),
    },
});

const searchForm = ref({
    search: props.filters.search || '',
    approval_status: props.filters.approval_status || '',
});

// Sync searchForm ketika props berubah (e.g. setelah navigasi paginasi)
watch(() => props.filters, (newFilters) => {
    searchForm.value.search = newFilters.search || '';
    searchForm.value.approval_status = newFilters.approval_status || '';
}, { deep: true });

const performSearch = () => {
    router.get(route('admin.go_care.index'), {
        search: searchForm.value.search,
        approval_status: searchForm.value.approval_status,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchForm.value.search = '';
    searchForm.value.approval_status = '';
    performSearch();
};

const showDetail = ref({});

const toggleDetail = (id) => {
    showDetail.value[id] = !showDetail.value[id];
};

const approve = (id) => {
    if (!confirm('Approve GO CARE ini? User akan mendapat poin.')) return;
    router.post(route('admin.go_care.approve', id), {}, { preserveScroll: true });
};

const reject = (id) => {
    const comment = prompt('Masukkan alasan reject (wajib):');
    if (!comment) return;
    router.post(route('admin.go_care.reject', id), { reject_comment: comment }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Data GO CARE - Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Data GO CARE
                </h2>
                <BackToDashboard admin />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-none px-4 sm:px-6 lg:px-8">
                <!-- Filter & Search -->
                <div class="mb-6 rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <form @submit.prevent="performSearch" class="space-y-4 md:space-y-0 md:flex md:items-end md:gap-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                                Pencarian
                            </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Cari berdasarkan nama, NPP, bagian, atau penjelasan..."
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-amber-500 focus:ring-offset-0 focus:shadow-lg focus:shadow-amber-100/50"
                            />
                        </div>

                        <div class="md:w-56">
                            <label for="approval_status" class="block text-sm font-medium text-gray-700 mb-2">
                                Status Approval
                            </label>
                            <select
                                id="approval_status"
                                v-model="searchForm.approval_status"
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-amber-500 focus:ring-offset-0 focus:shadow-lg focus:shadow-amber-100/50"
                            >
                                <option value="">Semua</option>
                                <option value="PENDING">PENDING</option>
                                <option value="APPROVED">APPROVED</option>
                                <option value="REJECTED">REJECTED</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="rounded-xl bg-amber-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-300/50 transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2"
                            >
                                Cari
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="rounded-xl bg-gray-200 px-6 py-2.5 text-sm font-semibold text-gray-700 shadow-lg transition hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                            >
                                Reset
                            </button>
                        </div>
                    </form>
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <MonthlyExcelExport export-route="admin.reports.go_care.export" />
                    </div>
                </div>

                <!-- Data Table -->
                <div class="rounded-2xl bg-white/90 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Tanggal
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Karyawan
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Bagian
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Bagian Temuan
                                    </th>
                                    <th class="w-48 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Approval
                                    </th>
                                    <th class="w-40 px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template v-for="goCare in goCares.data" :key="goCare.id">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {{ goCare.created_at }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div>
                                                <div class="font-medium">
                                                    {{ goCare.nama_karyawan || goCare.user_name }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    NPP: {{ goCare.npp_karyawan || goCare.user_npp || '-' }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ goCare.bagian }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ goCare.bagian_temuan }}
                                        </td>
                                        <td class="min-w-0 px-6 py-4 text-sm">
                                            <span
                                                :class="[
                                                    'inline-flex rounded-full px-2 py-1 text-xs font-semibold whitespace-nowrap',
                                                    goCare.approval_status === 'APPROVED'
                                                        ? 'bg-blue-100 text-blue-800'
                                                        : goCare.approval_status === 'REJECTED'
                                                        ? 'bg-red-100 text-red-800'
                                                        : 'bg-amber-100 text-amber-800',
                                                ]"
                                            >
                                                {{ goCare.approval_status || 'PENDING' }}
                                            </span>
                                            <div
                                                v-if="goCare.approval_status === 'REJECTED' && goCare.reject_comment"
                                                class="mt-1 text-xs text-red-700 break-words break-all whitespace-pre-wrap"
                                            >
                                                {{ goCare.reject_comment }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <template v-if="(goCare.approval_status || 'PENDING') === 'PENDING'">
                                                    <button
                                                        type="button"
                                                        @click="approve(goCare.id)"
                                                        class="inline-flex items-center rounded-lg bg-[#00529b] px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[#004080]"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="reject(goCare.id)"
                                                        class="inline-flex items-center rounded-lg bg-red-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700"
                                                    >
                                                        Reject
                                                    </button>
                                                </template>
                                                <button
                                                    @click="toggleDetail(goCare.id)"
                                                    class="text-amber-600 hover:text-amber-900 transition-colors"
                                                >
                                                    {{ showDetail[goCare.id] ? 'Sembunyikan' : 'Lihat Detail' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="showDetail[goCare.id]" class="bg-gray-50">
                                        <td colspan="6" class="border-t border-gray-200 p-6 overflow-hidden">
                                            <div class="grid min-w-0 grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Left Column -->
                                                <div class="min-w-0 space-y-4">
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Karyawan</h4>
                                                        <p class="text-sm text-gray-700 bg-white p-3 rounded-lg break-words">
                                                            {{ goCare.nama_karyawan || goCare.user_name }} (NPP:
                                                            {{ goCare.npp_karyawan || goCare.user_npp || '-' }})
                                                        </p>
                                                    </div>
                                                    <div v-if="goCare.area_temuan">
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Area Temuan</h4>
                                                        <p class="text-sm text-gray-700 bg-white p-3 rounded-lg break-words">{{ goCare.area_temuan }}</p>
                                                    </div>
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Penjelasan Temuan</h4>
                                                        <p class="text-sm text-gray-700 bg-white p-3 rounded-lg break-words whitespace-pre-wrap">
                                                            {{ goCare.penjelasan_temuan }}
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Penjelasan CAPA</h4>
                                                        <p class="text-sm text-gray-700 bg-white p-3 rounded-lg break-words whitespace-pre-wrap">
                                                            {{ goCare.penjelasan_capa }}
                                                        </p>
                                                    </div>

                                                    <div v-if="goCare.approval_status === 'REJECTED' && goCare.reject_comment">
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Alasan Reject</h4>
                                                        <p class="text-sm text-red-700 bg-red-50 p-3 rounded-lg break-words break-all whitespace-pre-wrap">
                                                            {{ goCare.reject_comment }}
                                                        </p>
                                                    </div>

                                                    <PhotoGallery
                                                        :images="goCare.foto_before"
                                                        title="Foto Before"
                                                        grid-class="grid-cols-2"
                                                    />
                                                </div>

                                                <!-- Right Column -->
                                                <div class="space-y-4">
                                                    <PhotoGallery
                                                        :images="goCare.foto_after"
                                                        title="Foto After"
                                                        grid-class="grid-cols-2"
                                                    />
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <PaginationBar :paginator="goCares" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

