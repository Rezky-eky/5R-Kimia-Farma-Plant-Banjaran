<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    sales: { type: Object, required: true },
    isAdmin: { type: Boolean, default: false },
});

const completeSale = (id) => {
    if (confirm('Selesaikan transaksi jual beli ini?')) {
        router.post(route('go_sale.complete', id));
    }
};
</script>

<template>
    <Head title="Go Sale - Penjualan Barang DBR" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                Go Sale – Penjualan Barang DBR
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-white shadow-xl ring-1 ring-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <p class="text-sm text-gray-600">
                            {{ isAdmin ? 'Semua transaksi jual barang DBR.' : 'Transaksi jual yang Anda buat atau yang melibatkan departemen Anda.' }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Barang</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Penjual</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Pembeli (Bagian)</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Harga</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-700">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="sale in sales.data" :key="sale.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ sale.created_at }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                        {{ sale.dbr_snapshot?.nama_barang || '-' }} ({{ sale.dbr_snapshot?.jumlah }} {{ sale.dbr_snapshot?.satuan }})
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ sale.seller_bagian }} ({{ sale.seller_name }})</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ sale.buyer_bagian }} <span v-if="sale.buyer_name" class="text-gray-500">({{ sale.buyer_name }})</span></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">Rp {{ Number(sale.agreed_price).toLocaleString('id-ID') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                sale.status === 'pending' && 'bg-amber-100 text-amber-800',
                                                sale.status === 'completed' && 'bg-blue-100 text-blue-800',
                                                sale.status === 'cancelled' && 'bg-red-100 text-red-800',
                                            ]"
                                        >
                                            {{ sale.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <button
                                            v-if="sale.status === 'pending' && !isAdmin && (sale.buyer_user_id ? sale.buyer_user_id === $page.props.auth?.user?.id : sale.buyer_bagian === $page.props.auth?.user?.bagian)"
                                            type="button"
                                            @click="completeSale(sale.id)"
                                            class="text-[#00529b] hover:text-[#004080] font-medium"
                                        >
                                            Selesaikan
                                        </button>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                </tr>
                                <tr v-if="sales.data.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">Belum ada transaksi jual.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="sales.links?.length > 3" class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                        <span class="text-sm text-gray-600">Hal {{ sales.current_page }} dari {{ sales.last_page }}</span>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in sales.links"
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
