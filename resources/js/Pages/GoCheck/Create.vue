<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    assignedBagian: { type: Array, default: () => [] },
});

const form = useForm({
    bagian: props.assignedBagian[0] ?? '',
    area_temuan: '',
    ruangan_temuan: '',
    penjelasan_temuan: '',
    pic_terkait: '',
    photo_temuan: [],
});

const photoPreviews = ref([]);
const maxFiles = 5;
const maxFileSize = 10 * 1024 * 1024;
const errorMessage = ref('');
const fileInputRef = ref(null);

const triggerCamera = () => {
    fileInputRef.value?.click();
};

const handleFileChange = (event) => {
    errorMessage.value = '';
    const files = Array.from(event.target.files || []);
    const currentCount = form.photo_temuan.length;
    const totalCount = currentCount + files.length;

    if (totalCount > maxFiles) {
        errorMessage.value = `Maksimal ${maxFiles} foto. Anda memilih ${totalCount} foto.`;
        event.target.value = '';
        return;
    }

    const validFiles = [];
    for (const file of files) {
        if (file.size > maxFileSize) {
            errorMessage.value = `File "${file.name}" melebihi 10MB dan diabaikan.`;
            continue;
        }
        validFiles.push(file);
    }

    form.photo_temuan = [...form.photo_temuan, ...validFiles];

    validFiles.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreviews.value.push({
                id: Date.now() + Math.random(),
                url: e.target.result,
                file,
            });
        };
        reader.readAsDataURL(file);
    });

    event.target.value = '';
};

const removePhoto = (previewId) => {
    const index = photoPreviews.value.findIndex((p) => p.id === previewId);
    if (index === -1) return;
    const preview = photoPreviews.value[index];
    form.photo_temuan = form.photo_temuan.filter((f) => f !== preview.file);
    photoPreviews.value.splice(index, 1);
};

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
                Anda sebagai <strong>Finder (Tim 5R)</strong> mencatat temuan audit. Bagian yang diaudit harus sesuai penugasan.
                Karyawan bagian terkait akan menjadi <strong>Solver</strong> melalui notifikasi.
            </p>

            <form @submit.prevent="submit" class="space-y-6">
                <section class="rounded-2xl bg-white p-6 shadow ring-1 ring-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Bagian yang diaudit</h3>
                    <InputLabel for="bagian" value="Bagian *" />
                    <select
                        id="bagian"
                        v-model="form.bagian"
                        required
                        class="mt-2 block w-full rounded-xl border-0 bg-white px-3 py-2 text-sm shadow-inner ring-1 ring-gray-200 focus:ring-2 focus:ring-[#00529b]"
                    >
                        <option v-for="b in assignedBagian" :key="b" :value="b">{{ b }}</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.bagian" />
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
                    <p class="text-sm text-gray-500 mb-4">Maksimal 5 foto, masing-masing maks. 10MB (JPG, PNG, GIF).</p>

                    <input
                        id="photo_temuan"
                        ref="fileInputRef"
                        type="file"
                        accept="image/*"
                        capture="environment"
                        multiple
                        class="hidden"
                        @change="handleFileChange"
                    />

                    <button
                        type="button"
                        :disabled="photoPreviews.length >= maxFiles"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-[#00529b]/40 bg-blue-50 px-4 py-4 text-sm font-semibold text-[#00529b] disabled:opacity-50"
                        @click="triggerCamera"
                    >
                        📷 Tambah / ambil foto
                    </button>

                    <p v-if="photoPreviews.length > 0" class="mt-2 text-xs text-gray-600">
                        Foto terpilih: <strong>{{ photoPreviews.length }}/{{ maxFiles }}</strong>
                    </p>
                    <div v-if="errorMessage" class="mt-2 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                        {{ errorMessage }}
                    </div>
                    <InputError class="mt-2" :message="form.errors.photo_temuan" />

                    <div v-if="photoPreviews.length > 0" class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div
                            v-for="(preview, index) in photoPreviews"
                            :key="preview.id"
                            class="relative rounded-xl border border-gray-200 bg-gray-50 p-2"
                        >
                            <button
                                type="button"
                                class="absolute -right-2 -top-2 z-10 h-6 w-6 rounded-full bg-red-500 text-white text-xs hover:bg-red-600"
                                aria-label="Hapus foto"
                                @click="removePhoto(preview.id)"
                            >
                                ×
                            </button>
                            <img :src="preview.url" :alt="`Preview ${index + 1}`" class="h-32 w-full rounded-lg object-cover" />
                            <p class="mt-1 text-center text-xs text-gray-500">Foto {{ index + 1 }}</p>
                        </div>
                    </div>
                </section>

                <div class="flex gap-3">
                    <PrimaryButton :disabled="form.processing">Simpan Go Check</PrimaryButton>
                    <Link :href="route('dashboard')" class="text-sm text-gray-600 hover:text-gray-900 py-2">Batal</Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
