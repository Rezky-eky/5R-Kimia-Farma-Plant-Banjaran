<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PhotoGallery from '@/Components/PhotoGallery.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    goCare: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Detail GO CARE" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Detail Laporan GO CARE
                </h2>
                <BackToDashboard admin />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-white/90 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60 overflow-hidden">
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Penjelasan Temuan</h4>
                                    <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">
                                        {{ goCare.penjelasan_temuan }}
                                    </p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Informasi</h4>
                                    <div class="bg-gray-50 p-3 rounded-lg space-y-2 text-sm">
                                        <div><strong>Tanggal:</strong> {{ goCare.created_at }}</div>
                                        <div>
                                            <strong>Karyawan:</strong>
                                            {{ goCare.nama_karyawan || goCare.user_name }} (NPP:
                                            {{ goCare.npp_karyawan || goCare.user_npp || '-' }})
                                        </div>
                                        <div><strong>Bagian:</strong> {{ goCare.bagian }}</div>
                                        <div><strong>Bagian Temuan:</strong> {{ goCare.bagian_temuan }}</div>
                                        <div><strong>Area Temuan:</strong> {{ goCare.area_temuan || '-' }}</div>
                                    </div>
                                </div>
                                <div v-if="goCare.penjelasan_capa">
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Penjelasan CAPA</h4>
                                    <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">
                                        {{ goCare.penjelasan_capa }}
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <PhotoGallery
                                    :images="goCare.foto_before"
                                    title="Foto Before"
                                    grid-class="grid-cols-2"
                                />
                                <PhotoGallery
                                    :images="goCare.foto_after"
                                    title="Foto After"
                                    grid-class="grid-cols-2"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
