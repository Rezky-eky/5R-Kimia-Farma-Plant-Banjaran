<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    offers: { type: Object, required: true },
    isAdmin: { type: Boolean, default: false },
});

const acceptOffer = (id) => {
    if (confirm('Terima tawaran ini?')) {
        router.post(route('go_offer.accept', id));
    }
};
const rejectOffer = (id) => {
    if (confirm('Tolak tawaran ini?')) {
        router.post(route('go_offer.reject', id));
    }
};
</script>

<template>
    <Head title="Go Offer - Tawaran Barang DBR" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                Go Offer – Tawaran Barang DBR
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-white shadow-xl ring-1 ring-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <p class="text-sm text-gray-600">
                            {{ isAdmin ? 'Semua tawaran barang DBR ke departemen lain.' : 'Tawaran yang Anda buat atau yang ditujukan ke departemen Anda.' }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Barang</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Dari Bagian</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Tujuan Bagian</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="offer in offers.data" :key="offer.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ offer.created_at }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                        {{ offer.dbr_snapshot?.nama_barang || '-' }} ({{ offer.dbr_snapshot?.jumlah }} {{ offer.dbr_snapshot?.satuan }})
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ offer.offered_by_bagian }} ({{ offer.offered_by_name }})</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <span v-if="offer.target_user_id">{{ offer.target_user_name }} ({{ offer.target_bagian }})</span>
                                        <span v-else>{{ offer.target_bagian }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                offer.status === 'pending' && 'bg-amber-100 text-amber-800',
                                                offer.status === 'accepted' && 'bg-blue-100 text-blue-800',
                                                offer.status === 'rejected' && 'bg-red-100 text-red-800',
                                            ]"
                                        >
                                            {{ offer.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <template v-if="offer.status === 'pending' && !isAdmin && (offer.target_user_id ? offer.target_user_id === $page.props.auth?.user?.id : offer.target_bagian === $page.props.auth?.user?.bagian)">
                                            <button
                                                type="button"
                                                @click="acceptOffer(offer.id)"
                                                class="text-[#00529b] hover:text-[#004080] font-medium mr-3"
                                            >
                                                Terima
                                            </button>
                                            <button
                                                type="button"
                                                @click="rejectOffer(offer.id)"
                                                class="text-red-600 hover:text-red-800 font-medium"
                                            >
                                                Tolak
                                            </button>
                                        </template>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                </tr>
                                <tr v-if="offers.data.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">Belum ada tawaran.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="offers.links?.length > 3" class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                        <span class="text-sm text-gray-600">Hal {{ offers.current_page }} dari {{ offers.last_page }}</span>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in offers.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="['px-3 py-1 rounded text-sm', link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
