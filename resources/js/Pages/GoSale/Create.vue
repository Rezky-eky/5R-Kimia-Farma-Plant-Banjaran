<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    goAction: { type: Object, required: true },
    dbrIndex: { type: Number, required: true },
    dbrItem: { type: Object, required: true },
    mode: { type: String, default: 'request' },
    userOptions: { type: Array, default: () => [] },
    departemenOptions: { type: Array, default: () => [] },
});

const form = useForm({
    go_action_id: props.goAction.id,
    dbr_index: props.dbrIndex,
    mode: props.mode,
    buyer_bagian: '',
    buyer_user_id: '',
    agreed_price: '',
});

const isMention = () => props.mode === 'mention';
</script>

<template>
    <Head :title="isMention() ? 'Jual ke User - Go Sale' : 'Ajukan Beli - Go Sale'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    {{ isMention() ? 'Jual Barang ke User' : 'Ajukan Beli Barang DBR' }}
                </h2>
                <Link :href="route('go_sale.index')" class="rounded-xl bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300">
                    ← Kembali
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl">
                <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-100">
                    <p class="text-sm text-gray-600 mb-4">
                        Barang: <strong>{{ dbrItem.nama_barang }}</strong> – {{ dbrItem.jumlah }} {{ dbrItem.satuan }} (Dari: {{ goAction.bagian }})
                    </p>

                    <!-- Mode: Meminta pembelian dari bagian lain -->
                    <form v-if="!isMention()" @submit.prevent="form.post(route('go_sale.store'))" class="space-y-6">
                        <p class="text-sm text-gray-700">
                            Anda mengajukan permintaan pembelian barang ini. Ajukan harga kesepakatan; pemilik barang (creator) dapat menyetujui atau menolak.
                        </p>
                        <div>
                            <InputLabel for="agreed_price" value="Harga yang diajukan (Rp) *" />
                            <input
                                id="agreed_price"
                                v-model="form.agreed_price"
                                type="number"
                                min="0"
                                step="1"
                                required
                                class="mt-2 block w-full rounded-xl border-0 bg-white px-3 py-2 text-sm text-gray-700 shadow-inner ring-1 ring-gray-200 focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0"
                                placeholder="0"
                            />
                            <InputError class="mt-2" :message="form.errors.agreed_price" />
                        </div>
                        <div class="flex gap-4">
                            <PrimaryButton type="submit" :disabled="form.processing">Ajukan Beli</PrimaryButton>
                            <Link :href="route('go_sale.index')" class="inline-flex items-center rounded-xl bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300">
                                Batal
                            </Link>
                        </div>
                    </form>

                    <!-- Mode: Jual ke User (nama + bagian) -->
                    <form v-else @submit.prevent="form.post(route('go_sale.store'))" class="space-y-6">
                        <div>
                            <InputLabel for="buyer_user_id" value="Jual ke User (Nama - Bagian) *" />
                            <select
                                id="buyer_user_id"
                                v-model="form.buyer_user_id"
                                required
                                class="mt-2 block w-full rounded-xl border-0 bg-white px-3 py-2 text-sm text-gray-700 shadow-inner ring-1 ring-gray-200 focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0"
                            >
                                <option value="">-- Pilih User --</option>
                                <option v-for="u in userOptions" :key="u.id" :value="u.id">{{ u.label }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.buyer_user_id" />
                        </div>
                        <div>
                            <InputLabel for="agreed_price" value="Harga Kesepakatan (Rp) *" />
                            <input
                                id="agreed_price"
                                v-model="form.agreed_price"
                                type="number"
                                min="0"
                                step="1"
                                required
                                class="mt-2 block w-full rounded-xl border-0 bg-white px-3 py-2 text-sm text-gray-700 shadow-inner ring-1 ring-gray-200 focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0"
                                placeholder="0"
                            />
                            <InputError class="mt-2" :message="form.errors.agreed_price" />
                        </div>
                        <div class="flex gap-4">
                            <PrimaryButton type="submit" :disabled="form.processing">Buat Penawaran Jual ke User</PrimaryButton>
                            <Link :href="route('go_sale.index')" class="inline-flex items-center rounded-xl bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300">
                                Batal
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
