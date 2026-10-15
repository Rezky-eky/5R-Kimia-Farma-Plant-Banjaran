<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PhotoGallery from '@/Components/PhotoGallery.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    goBoost: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Detail GO BOOST" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-2xl font-bold leading-tight text-gray-900">
                    Detail Laporan GO BOOST
                </h2>
                <Link
                    :href="route('notifications.index')"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Kembali ke Notifikasi
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white/90 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                        <div class="space-y-5">
                            <div>
                                <h3 class="mb-2 text-sm font-semibold text-gray-900">Penjelasan Temuan</h3>
                                <p class="whitespace-pre-wrap rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
                                    {{ goBoost.penjelasan_temuan }}
                                </p>
                            </div>
                            <div>
                                <h3 class="mb-2 text-sm font-semibold text-gray-900">Informasi Laporan</h3>
                                <dl class="space-y-2 rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
                                    <div><dt class="inline font-semibold">Tanggal:</dt> <dd class="inline">{{ goBoost.created_at }}</dd></div>
                                    <div><dt class="inline font-semibold">Karyawan:</dt> <dd class="inline">{{ goBoost.nama_karyawan }} (NPP: {{ goBoost.npp_karyawan }})</dd></div>
                                    <div><dt class="inline font-semibold">Bagian:</dt> <dd class="inline">{{ goBoost.bagian }}</dd></div>
                                    <div><dt class="inline font-semibold">Area Temuan:</dt> <dd class="inline">{{ goBoost.area_temuan }}</dd></div>
                                    <div><dt class="inline font-semibold">Ruangan Temuan:</dt> <dd class="inline">{{ goBoost.ruangan_temuan }}</dd></div>
                                    <div><dt class="inline font-semibold">PIC Terkait:</dt> <dd class="inline">{{ goBoost.pic_terkait }}</dd></div>
                                    <div v-if="goBoost.mentioned_user_name">
                                        <dt class="inline font-semibold">User yang di-mention:</dt>
                                        <dd class="inline">{{ goBoost.mentioned_user_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="inline font-semibold">Status:</dt>
                                        <dd class="ml-1 inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">
                                            {{ goBoost.status }}
                                        </dd>
                                    </div>
                                    <div v-if="goBoost.approval_status">
                                        <dt class="inline font-semibold">Status Approval:</dt>
                                        <dd class="ml-1">{{ goBoost.approval_status }}</dd>
                                    </div>
                                    <div v-if="goBoost.status_perbaikan">
                                        <dt class="inline font-semibold">Status Perbaikan:</dt>
                                        <dd class="ml-1">{{ goBoost.status_perbaikan }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div v-if="goBoost.keterangan_perbaikan">
                                <h3 class="mb-2 text-sm font-semibold text-gray-900">Keterangan Perbaikan</h3>
                                <p class="whitespace-pre-wrap rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
                                    {{ goBoost.keterangan_perbaikan }}
                                </p>
                                <p v-if="goBoost.tanggal_perbaikan" class="mt-2 text-xs text-gray-500">
                                    Selesai pada: {{ goBoost.tanggal_perbaikan }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-5">
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
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
