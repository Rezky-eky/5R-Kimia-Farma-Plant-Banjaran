<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Object, required: true },
    isAdmin: { type: Boolean, default: false },
});

const approveOffer = (id) => {
    if (confirm('Terima request ini?')) {
        router.post(route('go_offer.accept', id));
    }
};

const rejectOffer = (id) => {
    if (confirm('Tolak request ini?')) {
        router.post(route('go_offer.reject', id));
    }
};

const getRingkasStatusBadgeClass = (status) => {
    switch (status) {
        case 'available':
            return 'bg-gray-100 text-gray-700';
        case 'requested':
            return 'bg-amber-100 text-amber-800';
        case 'allocated':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};

const getRingkasStatusLabel = (status) => {
    switch (status) {
        case 'available':
            return 'available';
        case 'requested':
            return 'requested/interested';
        case 'allocated':
            return 'allocated/approved';
        default:
            return status || 'available';
    }
};
</script>

<template>
    <Head title="Go Offer - Ajukan Ambil & Approval" />
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
                            {{ isAdmin ? 'Semua item Go Offer.' : 'Ajukan Ambil untuk request, creator meng-approve/reject.' }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Barang</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Creator</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Diminta oleh</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in items.data" :key="item.go_action_id + '_' + item.dbr_index" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ item.created_at }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                        {{ item.dbr_snapshot?.nama_barang || '-' }} ({{ item.dbr_snapshot?.jumlah }} {{ item.dbr_snapshot?.satuan }})
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ item.creator_bagian }} ({{ item.creator_name }})</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ item.ringkas_status === 'requested' || item.ringkas_status === 'allocated' ? (item.requested_by_name || '-') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                getRingkasStatusBadgeClass(item.ringkas_status),
                                            ]"
                                        >
                                            {{ getRingkasStatusLabel(item.ringkas_status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex justify-end gap-3">
                                            <!-- Ajukan Ambil: hanya user non-creator dan saat status available -->
                                            <template v-if="item.creator_user_id !== $page.props.auth?.user?.id">
                                                <Link
                                                    v-if="item.ringkas_status === 'available'"
                                                    :href="route('go_offer.create', { go_action_id: item.go_action_id, dbr_index: item.dbr_index, mode: 'request' })"
                                                    class="text-[#00529b] hover:text-[#004080] font-medium"
                                                >
                                                    Ajukan Ambil
                                                </Link>
                                                <span v-else class="text-gray-400">-</span>
                                            </template>

                                            <!-- Approval: hanya creator ketika status requested -->
                                            <template v-else>
                                                <button
                                                    v-if="item.ringkas_status === 'requested' && item.active_offer_id"
                                                    type="button"
                                                    @click="approveOffer(item.active_offer_id)"
                                                    class="text-[#00529b] hover:text-[#004080] font-medium"
                                                >
                                                    Terima
                                                </button>
                                                <button
                                                    v-if="item.ringkas_status === 'requested' && item.active_offer_id"
                                                    type="button"
                                                    @click="rejectOffer(item.active_offer_id)"
                                                    class="text-red-600 hover:text-red-800 font-medium"
                                                >
                                                    Tolak
                                                </button>
                                                <span v-else class="text-gray-400">-</span>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="items.data.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">Belum ada item Go Offer.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="items.links?.length > 3" class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                        <span class="text-sm text-gray-600">Hal {{ items.current_page }} dari {{ items.last_page }}</span>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in items.links"
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
