<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import PhotoGallery from '@/Components/PhotoGallery.vue';
import MonthlyExcelExport from '@/Components/MonthlyExcelExport.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    goChecks: Object,
    filters: Object,
});

const searchForm = useForm({
    search: props.filters.search || '',
    approval_status: props.filters.approval_status || '',
});

const performSearch = () => {
    router.get(route('go_check.management.index'), searchForm, { preserveState: true, preserveScroll: true });
};

const showDetail = ref({});

const approve = (id) => {
    if (!confirm('Approve Go Check ini? Finder & Solver masing-masing +10 poin.')) return;
    router.post(route('go_check.management.approve', id), {}, { preserveScroll: true });
};

const reject = (id) => {
    const comment = prompt('Alasan reject (wajib):');
    if (!comment?.trim()) return;
    router.post(route('go_check.management.reject', id), { reject_comment: comment }, { preserveScroll: true });
};

const statusBadgeClass = (status) => {
    switch (status) {
        case 'APPROVED':
            return 'bg-green-100 text-green-800';
        case 'REJECTED':
            return 'bg-red-100 text-red-800';
        case 'MENUNGGU_SOLVER':
            return 'bg-slate-100 text-slate-700';
        default:
            return 'bg-amber-100 text-amber-800';
    }
};

const statusLabel = (status) => {
    if (status === 'MENUNGGU_SOLVER') return 'Menunggu Solver';
    return status || 'PENDING';
};
</script>

<template>
    <Head title="Data Go Check" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold text-gray-900">Data Go Check</h2>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('go_check.management.dashboard')"
                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Panel Manajemen
                    </Link>
                    <BackToDashboard />
                </div>
            </div>
        </template>

        <div class="py-8 px-4 max-w-7xl mx-auto">
            <p class="mb-4 text-sm text-gray-600">
                <strong>Approve/Reject</strong> tersedia setelah Solver bagian terkait menginput perbaikan (status selesai).
                Foto temuan dari Tim 5R (Finder) dan foto perbaikan (Solver) ditampilkan di detail.
            </p>

            <form @submit.prevent="performSearch" class="mb-6 flex flex-wrap gap-3 items-end">
                <input v-model="searchForm.search" type="text" placeholder="Cari bagian, ruangan..." class="rounded-lg border-gray-200 text-sm flex-1 min-w-[200px]" />
                <select v-model="searchForm.approval_status" class="rounded-lg border-gray-200 text-sm">
                    <option value="">Semua status</option>
                    <option value="MENUNGGU_SOLVER">Menunggu Solver</option>
                    <option value="PENDING">Pending approval</option>
                    <option value="APPROVED">Approved</option>
                    <option value="REJECTED">Rejected</option>
                </select>
                <button type="submit" class="rounded-lg bg-[#00529b] text-white px-4 py-2 text-sm font-semibold">Filter</button>
            </form>

            <div class="mb-6 rounded-xl bg-white p-4 shadow ring-1 ring-gray-100">
                <MonthlyExcelExport export-route="go_check.management.reports.go_check.export" />
            </div>

            <div class="space-y-4">
                <div v-for="row in goChecks.data" :key="row.id" class="rounded-xl bg-white p-5 shadow ring-1 ring-gray-100">
                    <div class="flex flex-wrap justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="statusBadgeClass(row.approval_status)">
                                {{ statusLabel(row.approval_status) }}
                            </span>
                            <span v-if="row.status_perbaikan === 'pending'" class="ml-2 text-xs text-gray-500">· Perbaikan belum diinput</span>
                            <h3 class="font-semibold text-gray-900 mt-2">{{ row.bagian }} — {{ row.ruangan_temuan }}</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                Finder (Tim 5R): <strong>{{ row.finder_name }}</strong> ({{ row.finder_npp }}) ·
                                {{ row.created_at }}
                            </p>
                            <p class="text-sm text-gray-600">
                                Solver: <strong>{{ row.solver_name || 'Belum ada' }}</strong>
                                <span v-if="row.tanggal_perbaikan"> · {{ row.tanggal_perbaikan }}</span>
                            </p>
                        </div>
                        <div v-if="row.can_approve_reject" class="flex flex-col gap-2 shrink-0">
                            <button
                                type="button"
                                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                                @click="approve(row.id)"
                            >
                                Approve
                            </button>
                            <button
                                type="button"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                                @click="reject(row.id)"
                            >
                                Reject
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="text-sm text-[#00529b] font-medium mt-3"
                        @click="showDetail[row.id] = !showDetail[row.id]"
                    >
                        {{ showDetail[row.id] ? 'Sembunyikan detail' : 'Lihat detail & foto' }}
                    </button>

                    <div v-if="showDetail[row.id]" class="mt-4 border-t pt-4 space-y-4 text-sm text-gray-700">
                        <div>
                            <p><strong>Area:</strong> {{ row.area_temuan }}</p>
                            <p class="mt-1"><strong>Temuan:</strong> {{ row.penjelasan_temuan }}</p>
                            <p v-if="row.pic_terkait" class="mt-1"><strong>PIC:</strong> {{ row.pic_terkait }}</p>
                        </div>

                        <PhotoGallery
                            v-if="row.photo_temuan_urls?.length"
                            :images="row.photo_temuan_urls"
                            title="Foto temuan (Finder / Tim 5R)"
                            grid-class="grid-cols-2 sm:grid-cols-3 md:grid-cols-4"
                            thumbnail-height-class="h-28"
                        />
                        <p v-else class="text-gray-400 italic">Tidak ada foto temuan.</p>

                        <div v-if="row.keterangan_perbaikan">
                            <p class="font-semibold text-gray-900">Perbaikan (Solver)</p>
                            <p class="mt-1">{{ row.keterangan_perbaikan }}</p>
                        </div>

                        <PhotoGallery
                            v-if="row.foto_perbaikan_urls?.length"
                            :images="row.foto_perbaikan_urls"
                            title="Foto perbaikan (Solver)"
                            grid-class="grid-cols-2 sm:grid-cols-3 md:grid-cols-4"
                            thumbnail-height-class="h-28"
                        />

                        <p v-if="row.reject_comment" class="text-red-700 bg-red-50 p-3 rounded-lg break-words break-all whitespace-pre-wrap">
                            <strong>Catatan reject:</strong> {{ row.reject_comment }}
                        </p>
                    </div>
                </div>

                <p v-if="!goChecks.data?.length" class="text-center text-gray-500 py-10">Belum ada data Go Check.</p>
            </div>

            <div class="mt-6 overflow-hidden rounded-xl bg-white shadow ring-1 ring-gray-100">
                <PaginationBar :paginator="goChecks" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
