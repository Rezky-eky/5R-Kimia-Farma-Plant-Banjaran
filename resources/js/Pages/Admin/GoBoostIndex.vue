<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import PhotoGallery from '@/Components/PhotoGallery.vue';
import MonthlyExcelExport from '@/Components/MonthlyExcelExport.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    goBoosts: {
        type: Object,
        required: true,
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: '',
            status_perbaikan: '',
        }),
    },
});

const searchForm = ref({
    search: props.filters.search || '',
    status: props.filters.status || '',
    status_perbaikan: props.filters.status_perbaikan || '',
});

// Sync searchForm ketika props berubah (e.g. setelah navigasi paginasi)
watch(() => props.filters, (newFilters) => {
    searchForm.value.search = newFilters.search || '';
    searchForm.value.status = newFilters.status || '';
    searchForm.value.status_perbaikan = newFilters.status_perbaikan || '';
}, { deep: true });

const performSearch = () => {
    router.get(route('admin.go_boost.index'), {
        search: searchForm.value.search,
        status: searchForm.value.status,
        status_perbaikan: searchForm.value.status_perbaikan,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchForm.value.search = '';
    searchForm.value.status = '';
    searchForm.value.status_perbaikan = '';
    performSearch();
};

const showDetail = ref({});

const toggleDetail = (id) => {
    showDetail.value[id] = !showDetail.value[id];
};

const approve = (id) => {
    if (!confirm('Approve GO BOOST ini? Finder & Solver akan mendapat poin.')) return;
    router.post(route('admin.go_boost.approve', id), {}, { preserveScroll: true });
};

const reject = (id) => {
    const comment = prompt('Masukkan alasan reject (wajib):');
    if (!comment) return;
    router.post(route('admin.go_boost.reject', id), { reject_comment: comment }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Data GO BOOST - Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Data GO BOOST
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
                                placeholder="Cari berdasarkan nama, NPP, area, ruangan, atau penjelasan..."
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-purple-500 focus:ring-offset-0 focus:shadow-lg focus:shadow-purple-100/50"
                            />
                        </div>

                        <div class="md:w-48">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Status
                            </label>
                            <select
                                id="status"
                                v-model="searchForm.status"
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-purple-500 focus:ring-offset-0 focus:shadow-lg focus:shadow-purple-100/50"
                            >
                                <option value="">Semua Status</option>
                                <option value="OPEN">OPEN</option>
                                <option value="CLOSED">CLOSED</option>
                            </select>
                        </div>

                        <div class="md:w-48">
                            <label for="status_perbaikan" class="block text-sm font-medium text-gray-700 mb-2">
                                Status Perbaikan
                            </label>
                            <select
                                id="status_perbaikan"
                                v-model="searchForm.status_perbaikan"
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-purple-500 focus:ring-offset-0 focus:shadow-lg focus:shadow-purple-100/50"
                            >
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="dalam_perbaikan">Dalam Perbaikan</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="rounded-xl bg-purple-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-purple-300/50 transition hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2"
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
                        <MonthlyExcelExport
                        v-if="isAdmin"
                        export-route="admin.reports.go_boost.export"
                    />
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
                                        Area & Ruangan
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        PIC Terkait
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Status
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
                                <template v-for="goBoost in goBoosts.data" :key="goBoost.id">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {{ goBoost.created_at }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div>
                                                <div class="font-medium">{{ goBoost.nama_karyawan }}</div>
                                                <div class="text-xs text-gray-500">NPP: {{ goBoost.npp_karyawan }}</div>
                                                <div v-if="goBoost.mentioned_user_name" class="text-xs text-purple-600 mt-1">
                                                    Mention: {{ goBoost.mentioned_user_name }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div>
                                                <div class="font-medium">{{ goBoost.area_temuan }}</div>
                                                <div class="text-xs text-gray-500">{{ goBoost.ruangan_temuan }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ goBoost.pic_terkait }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            <div class="space-y-1">
                                                <span
                                                    :class="[
                                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                        goBoost.status === 'CLOSED'
                                                            ? 'bg-blue-100 text-blue-800'
                                                            : 'bg-yellow-100 text-yellow-800',
                                                    ]"
                                                >
                                                    {{ goBoost.status }}
                                                </span>
                                                <div>
                                                    <span
                                                        :class="[
                                                            'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                            goBoost.status_perbaikan === 'selesai'
                                                                ? 'bg-blue-100 text-blue-800'
                                                                : goBoost.status_perbaikan === 'dalam_perbaikan'
                                                                ? 'bg-blue-100 text-blue-800'
                                                                : 'bg-gray-100 text-gray-800',
                                                        ]"
                                                    >
                                                        {{ goBoost.status_perbaikan }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="min-w-0 px-6 py-4 text-sm">
                                            <span
                                                :class="[
                                                    'inline-flex rounded-full px-2 py-1 text-xs font-semibold whitespace-nowrap',
                                                    goBoost.approval_status === 'APPROVED'
                                                        ? 'bg-blue-100 text-blue-800'
                                                        : goBoost.approval_status === 'REJECTED'
                                                        ? 'bg-red-100 text-red-800'
                                                        : goBoost.approval_status === 'PENDING'
                                                        ? 'bg-amber-100 text-amber-800'
                                                        : 'bg-gray-100 text-gray-800',
                                                ]"
                                            >
                                                {{ goBoost.approval_status || '-' }}
                                            </span>
                                            <div
                                                v-if="goBoost.approval_status === 'REJECTED' && goBoost.reject_comment"
                                                class="mt-1 text-xs text-red-700 break-words break-all whitespace-pre-wrap"
                                            >
                                                {{ goBoost.reject_comment }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <template v-if="isAdmin && goBoost.status_perbaikan === 'selesai' && (goBoost.approval_status === 'PENDING')">
                                                    <button
                                                        type="button"
                                                        @click="approve(goBoost.id)"
                                                        class="inline-flex items-center rounded-lg bg-[#00529b] px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[#004080]"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="reject(goBoost.id)"
                                                        class="inline-flex items-center rounded-lg bg-red-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700"
                                                    >
                                                        Reject
                                                    </button>
                                                </template>
                                                <button
                                                    @click="toggleDetail(goBoost.id)"
                                                    class="text-purple-600 hover:text-purple-900 transition-colors"
                                                >
                                                    {{ showDetail[goBoost.id] ? 'Sembunyikan' : 'Lihat Detail' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="showDetail[goBoost.id]" class="bg-gray-50">
                                        <td colspan="7" class="border-t border-gray-200 p-6 overflow-hidden">
                                            <div class="grid min-w-0 grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Left Column -->
                                                <div class="min-w-0 space-y-4">
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Penjelasan Temuan</h4>
                                                        <p class="text-sm text-gray-700 bg-white p-3 rounded-lg break-words whitespace-pre-wrap">
                                                            {{ goBoost.penjelasan_temuan }}
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Informasi</h4>
                                                        <div class="bg-white p-3 rounded-lg space-y-2 text-sm">
                                                            <div><strong>Bagian:</strong> {{ goBoost.bagian }}</div>
                                                            <div><strong>Area Temuan:</strong> {{ goBoost.area_temuan }}</div>
                                                            <div><strong>Ruangan Temuan:</strong> {{ goBoost.ruangan_temuan }}</div>
                                                            <div><strong>PIC Terkait:</strong> {{ goBoost.pic_terkait }}</div>
                                                            <div v-if="goBoost.mentioned_user_name">
                                                                <strong>User yang di-mention:</strong> {{ goBoost.mentioned_user_name }}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div v-if="goBoost.approval_status === 'REJECTED' && goBoost.reject_comment">
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Alasan Reject</h4>
                                                        <p class="text-sm text-red-700 bg-red-50 p-3 rounded-lg break-words break-all whitespace-pre-wrap">
                                                            {{ goBoost.reject_comment }}
                                                        </p>
                                                    </div>

                                                    <div v-if="goBoost.keterangan_perbaikan">
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Keterangan Perbaikan</h4>
                                                        <p class="text-sm text-gray-700 bg-white p-3 rounded-lg break-words whitespace-pre-wrap">
                                                            {{ goBoost.keterangan_perbaikan }}
                                                        </p>
                                                        <p v-if="goBoost.tanggal_perbaikan" class="text-xs text-gray-500 mt-2">
                                                            Selesai pada: {{ goBoost.tanggal_perbaikan }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Right Column -->
                                                <div class="space-y-4">
                                                    <PhotoGallery
                                                        :images="goBoost.foto_temuan"
                                                        title="Foto Temuan"
                                                        grid-class="grid-cols-2"
                                                    />
                                                    <PhotoGallery
                                                        :images="goBoost.foto_perbaikan"
                                                        title="Foto Perbaikan"
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

                    <PaginationBar :paginator="goBoosts" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

