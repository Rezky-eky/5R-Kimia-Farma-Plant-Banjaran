<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PhotoImagePicker from '@/Components/PhotoImagePicker.vue';

const props = defineProps({
    assignedBagian: { type: Array, default: () => [] },
    solverLeaders: { type: Array, default: () => [] },
});

const form = useForm({
    bagian: props.assignedBagian[0] ?? '',
    solver_user_id: props.solverLeaders[0]?.id ?? '',
    area_temuan: '',
    ruangan_temuan: '',
    penjelasan_temuan: '',
    pic_terkait: '',
    photo_temuan: [],
});

const submit = () => {
    form.post(route('go_check.store'), { forceFormData: true });
};
</script>

<template>
    <Head title="Go Check - Audit 5R" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-gray-900">Go Check — Temuan Audit</h2>
        </template>

        <div class="py-8 max-w-4xl mx-auto px-4">
            <p class="mb-6 text-sm text-gray-600">
                Anda sebagai <strong>Finder (Tim 5R)</strong> mencatat temuan audit. Pilih bagian yang diaudit dan tentukan
                <strong>Solver (ketua tim inspector)</strong> yang akan menindaklanjuti perbaikan.
            </p>

            <form @submit.prevent="submit" class="space-y-6">
                <section class="rounded-2xl bg-white p-6 shadow ring-1 ring-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Bagian & Solver</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="bagian" value="Bagian / area penugasan *" />
                            <select
                                id="bagian"
                                v-model="form.bagian"
                                required
                                class="mt-2 block w-full rounded-xl border-0 bg-white px-3 py-2 text-sm shadow-inner ring-1 ring-gray-200 focus:ring-2 focus:ring-[#00529b]"
                            >
                                <option v-for="b in assignedBagian" :key="b" :value="b">{{ b }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.bagian" />
                        </div>
                        <div>
                            <InputLabel for="solver_user_id" value="Solver (Ketua tim inspector) *" />
                            <select
                                id="solver_user_id"
                                v-model="form.solver_user_id"
                                required
                                class="mt-2 block w-full rounded-xl border-0 bg-white px-3 py-2 text-sm shadow-inner ring-1 ring-gray-200 focus:ring-2 focus:ring-[#00529b]"
                            >
                                <option value="" disabled>Pilih ketua tim</option>
                                <option
                                    v-for="solver in solverLeaders"
                                    :key="solver.id"
                                    :value="solver.id"
                                    :title="`${solver.name} (NPP: ${solver.npp}) — Ketua ${solver.team_name || 'Tim inspector'}`"
                                >
                                    {{ solver.name }} — Ketua {{ solver.team_name || 'Tim inspector' }}
                                </option>
                            </select>
                            <p v-if="!solverLeaders.length" class="mt-2 text-xs text-amber-700">
                                Belum ada ketua tim inspector. Tandai anggota sebagai ketua tim di Kelola Go Check.
                            </p>
                            <InputError class="mt-2" :message="form.errors.solver_user_id" />
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow ring-1 ring-gray-100">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="area_temuan" value="Area temuan *" />
                            <TextInput id="area_temuan" v-model="form.area_temuan" class="mt-2 w-full" required placeholder="Contoh: Area Produksi" />
                            <InputError class="mt-2" :message="form.errors.area_temuan" />
                        </div>
                        <div>
                            <InputLabel for="ruangan_temuan" value="Ruangan/tempat *" />
                            <TextInput id="ruangan_temuan" v-model="form.ruangan_temuan" class="mt-2 w-full" required placeholder="Contoh: Ruangan A-101" />
                            <InputError class="mt-2" :message="form.errors.ruangan_temuan" />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel for="penjelasan_temuan" value="Penjelasan temuan *" />
                            <textarea
                                id="penjelasan_temuan"
                                v-model="form.penjelasan_temuan"
                                rows="4"
                                required
                                class="mt-2 block w-full rounded-xl px-3 py-2 text-sm ring-1 ring-gray-200 focus:ring-2 focus:ring-[#00529b]"
                                placeholder="Jelaskan temuan audit 5R..."
                            />
                            <InputError class="mt-2" :message="form.errors.penjelasan_temuan" />
                        </div>
                        <div>
                            <InputLabel for="pic_terkait" value="PIC terkait (opsional)" />
                            <TextInput id="pic_terkait" v-model="form.pic_terkait" class="mt-2 w-full" />
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow ring-1 ring-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Foto temuan</h3>
                    <PhotoImagePicker
                        v-model="form.photo_temuan"
                        input-id="go-check-photo-temuan"
                        label=""
                        hint="Maksimal 5 foto, masing-masing maks. 10MB (JPG, PNG, GIF). Ambil foto langsung atau pilih dari galeri."
                    >
                        <InputError class="mt-2" :message="form.errors.photo_temuan" />
                    </PhotoImagePicker>
                </section>

                <div class="flex gap-3">
                    <PrimaryButton :disabled="form.processing || !solverLeaders.length">Simpan Go Check</PrimaryButton>
                    <Link :href="route('dashboard')" class="text-sm text-gray-600 hover:text-gray-900 py-2">Batal</Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
