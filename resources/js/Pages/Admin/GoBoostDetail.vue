<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Detail Laporan GO BOOST
                </h2>
                <Link
                    :href="route('admin.audit.index')"
                    class="inline-flex items-center rounded-xl bg-gray-500 px-4 py-2 text-sm font-semibold text-white shadow-lg transition hover:bg-gray-600"
                >
                    ← Kembali ke Laporan 5R
                </Link>
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
                                        {{ goBoost.penjelasan_temuan }}
                                    </p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Informasi</h4>
                                    <div class="bg-gray-50 p-3 rounded-lg space-y-2 text-sm">
                                        <div><strong>Tanggal:</strong> {{ goBoost.created_at }}</div>
                                        <div><strong>Karyawan:</strong> {{ goBoost.nama_karyawan }} (NPP: {{ goBoost.npp_karyawan }})</div>
                                        <div><strong>Bagian:</strong> {{ goBoost.bagian }}</div>
                                        <div><strong>Area Temuan:</strong> {{ goBoost.area_temuan }}</div>
                                        <div><strong>Ruangan Temuan:</strong> {{ goBoost.ruangan_temuan }}</div>
                                        <div><strong>PIC Terkait:</strong> {{ goBoost.pic_terkait }}</div>
                                        <div v-if="goBoost.mentioned_user_name">
                                            <strong>User yang di-mention:</strong> {{ goBoost.mentioned_user_name }}
                                        </div>
                                        <div>
                                            <strong>Status:</strong>
                                            <span
                                                :class="[
                                                    'ml-1 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                                    goBoost.status === 'CLOSED' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800',
                                                ]"
                                            >
                                                {{ goBoost.status }}
                                            </span>
                                        </div>
                                        <div v-if="goBoost.status_perbaikan">
                                            <strong>Status Perbaikan:</strong>
                                            <span class="ml-1 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-800">
                                                {{ goBoost.status_perbaikan }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="goBoost.keterangan_perbaikan">
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Keterangan Perbaikan</h4>
                                    <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">
                                        {{ goBoost.keterangan_perbaikan }}
                                    </p>
                                    <p v-if="goBoost.tanggal_perbaikan" class="text-xs text-gray-500 mt-2">
                                        Selesai pada: {{ goBoost.tanggal_perbaikan }}
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div v-if="goBoost.foto_temuan && goBoost.foto_temuan.length > 0">
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Foto Temuan</h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        <a
                                            v-for="(foto, index) in goBoost.foto_temuan"
                                            :key="index"
                                            :href="foto"
                                            target="_blank"
                                            class="block"
                                        >
                                            <img
                                                :src="foto"
                                                :alt="`Foto temuan ${index + 1}`"
                                                class="w-full h-32 object-cover rounded-lg border border-gray-200 hover:opacity-90"
                                            />
                                        </a>
                                    </div>
                                </div>
                                <div v-if="goBoost.foto_perbaikan && goBoost.foto_perbaikan.length > 0">
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Foto Perbaikan</h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        <a
                                            v-for="(foto, index) in goBoost.foto_perbaikan"
                                            :key="index"
                                            :href="foto"
                                            target="_blank"
                                            class="block"
                                        >
                                            <img
                                                :src="foto"
                                                :alt="`Foto perbaikan ${index + 1}`"
                                                class="w-full h-32 object-cover rounded-lg border border-gray-200 hover:opacity-90"
                                            />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
