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
    mode: { type: String, default: 'request' }, // request = meminta dari bagian lain, mention = tawarkan ke user
    targetOptions: { type: Array, default: () => [] },
    userOptions: { type: Array, default: () => [] },
});

const form = useForm({
    go_action_id: props.goAction.id,
    dbr_index: props.dbrIndex,
    mode: props.mode,
    target_bagian: '',
    target_user_id: '',
});

const isMention = () => props.mode === 'mention';
</script>

<template>
    <Head :title="isMention() ? 'Tawarkan ke User - Go Offer' : 'Meminta Barang - Go Offer'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    {{ isMention() ? 'Tawarkan Barang ke User' : 'Meminta Barang dari Bagian Lain' }}
                </h2>
                <Link :href="route('go_offer.index')" class="rounded-xl bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300">
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

                    <!-- Mode: Meminta dari bagian lain -->
                    <form v-if="!isMention()" @submit.prevent="form.post(route('go_offer.store'))" class="space-y-6">
                        <p class="text-sm text-gray-700">
                            Anda meminta barang ini dari <strong>{{ goAction.bagian }}</strong>. Tawaran akan ditujukan ke bagian Anda dan pemilik barang dapat menerima atau menolak.
                        </p>
                        <div class="flex gap-4">
                            <PrimaryButton type="submit" :disabled="form.processing">Kirim Permintaan Tawaran</PrimaryButton>
                            <Link :href="route('go_offer.index')" class="inline-flex items-center rounded-xl bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300">
                                Batal
                            </Link>
                        </div>
                    </form>

                    <!-- Mode: Tawarkan ke User (nama + bagian) -->
                    <form v-else @submit.prevent="form.post(route('go_offer.store'))" class="space-y-6">
                        <div>
                            <InputLabel for="target_user_id" value="Tawarkan ke User (Nama - Bagian) *" />
                            <select
                                id="target_user_id"
                                v-model="form.target_user_id"
                                required
                                class="mt-2 block w-full rounded-xl border-0 bg-white px-3 py-2 text-sm text-gray-700 shadow-inner ring-1 ring-gray-200 focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0"
                            >
                                <option value="">-- Pilih User --</option>
                                <option v-for="u in userOptions" :key="u.id" :value="u.id">{{ u.label }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.target_user_id" />
                        </div>
                        <div class="flex gap-4">
                            <PrimaryButton type="submit" :disabled="form.processing">Buat Tawaran ke User</PrimaryButton>
                            <Link :href="route('go_offer.index')" class="inline-flex items-center rounded-xl bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300">
                                Batal
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
