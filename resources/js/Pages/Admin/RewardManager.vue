<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    rewards: {
        type: Object,
        required: true,
    },
});

const showForm = ref(false);
const editingReward = ref(null);
const imagePreview = ref(null);

const form = useForm({
    title: '',
    points_required: '',
    stock: '',
    image: null,
    is_active: true,
});

const openCreateForm = () => {
    editingReward.value = null;
    form.reset();
    form.clearErrors();
    imagePreview.value = null;
    showForm.value = true;
};

const openEditForm = (reward) => {
    editingReward.value = reward;
    form.title = reward.title;
    form.points_required = reward.points_required;
    form.stock = reward.stock;
    form.is_active = reward.is_active;
    form.image = null;
    imagePreview.value = reward.image_path;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingReward.value = null;
    form.reset();
    form.clearErrors();
    imagePreview.value = null;
};

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    if (editingReward.value) {
        form.put(route('admin.reward.update', editingReward.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeForm();
            },
        });
    } else {
        form.post(route('admin.reward.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeForm();
            },
        });
    }
};

const deleteReward = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus reward ini?')) {
        router.delete(route('admin.reward.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Kelola Rewards - Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Kelola Katalog Rewards
                </h2>
                <div v-if="!showForm" class="flex gap-3">
                    <button
                        @click="openCreateForm"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-[#00529b] focus:ring-offset-2"
                                        style="background-color: #00529b; box-shadow: 0 10px 15px -3px rgba(0, 82, 155, 0.3);"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Reward
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Form Create/Edit -->
                <div v-if="showForm" class="rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ editingReward ? 'Edit Reward' : 'Tambah Reward Baru' }}
                        </h3>
                        <button
                            @click="closeForm"
                            class="text-gray-400 hover:text-gray-600 transition"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="title" value="Judul Reward *" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-2 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.title" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="points_required" value="Poin yang Diperlukan *" />
                                <TextInput
                                    id="points_required"
                                    v-model="form.points_required"
                                    type="number"
                                    min="1"
                                    class="mt-2 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.points_required" />
                            </div>

                            <div>
                                <InputLabel for="stock" value="Stok *" />
                                <TextInput
                                    id="stock"
                                    v-model="form.stock"
                                    type="number"
                                    min="0"
                                    class="mt-2 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.stock" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="image" value="Gambar Reward" />
                            <input
                                id="image"
                                type="file"
                                accept="image/*"
                                @change="handleImageChange"
                                class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <InputError class="mt-2" :message="form.errors.image" />
                            <div v-if="imagePreview" class="mt-4">
                                <img
                                    :src="imagePreview"
                                    alt="Preview"
                                    class="h-32 w-32 object-cover rounded-lg border border-gray-200"
                                />
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 border-gray-300 rounded focus:ring-[#00529b] accent-[#00529b]"
                            />
                            <InputLabel for="is_active" value="Aktif" class="ml-2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <button
                                type="button"
                                @click="closeForm"
                                class="inline-flex items-center justify-center rounded-xl bg-gray-200 px-5 py-2 text-sm font-semibold text-gray-700 shadow-md transition hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                            >
                                Batal
                            </button>
                            <PrimaryButton :disabled="form.processing">
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>{{ editingReward ? 'Update' : 'Simpan' }}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Daftar Rewards -->
                <div class="rounded-2xl bg-white/90 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="px-8 py-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Daftar Rewards
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Total: {{ rewards.total }} rewards
                        </p>
                    </div>

                    <div class="p-6">
                        <div v-if="rewards.data.length === 0" class="text-center py-10 text-gray-500">
                            Belum ada reward. Klik tombol "Tambah Reward" untuk menambahkan.
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="reward in rewards.data"
                                :key="reward.id"
                                class="rounded-xl border border-gray-200 bg-white p-4 shadow-lg hover:shadow-xl transition"
                            >
                                <div class="relative">
                                    <img
                                        v-if="reward.image_path"
                                        :src="reward.image_path"
                                        :alt="reward.title"
                                        class="w-full h-48 object-cover rounded-lg mb-4"
                                    />
                                    <div v-else class="w-full h-48 bg-gray-100 rounded-lg mb-4 flex items-center justify-center">
                                        <span class="text-gray-400 text-sm">Tidak ada gambar</span>
                                    </div>
                                    <span
                                        v-if="!reward.is_active"
                                        class="absolute top-2 right-2 px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded"
                                    >
                                        Nonaktif
                                    </span>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">{{ reward.title }}</h4>
                                <div class="space-y-2 mb-4">
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium">Poin:</span> {{ reward.points_required }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium">Stok:</span> {{ reward.stock }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        @click="openEditForm(reward)"
                                        class="flex-1 inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
                                    >
                                        Edit
                                    </button>
                                    <DangerButton
                                        @click="deleteReward(reward.id)"
                                        class="flex-1"
                                    >
                                        Hapus
                                    </DangerButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="rewards.links && rewards.links.length > 3" class="px-8 py-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Menampilkan {{ rewards.from }} sampai {{ rewards.to }} dari {{ rewards.total }} hasil
                        </div>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in rewards.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition',
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : link.url
                                        ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

