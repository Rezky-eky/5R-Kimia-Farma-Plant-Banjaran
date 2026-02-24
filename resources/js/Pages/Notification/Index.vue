<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    notifications: {
        type: Object,
        required: true,
    },
});

const markAsRead = (id) => {
    router.post(route('notifications.markAsRead', id), {}, {
        preserveScroll: true,
    });
};

const markAllAsRead = () => {
    router.post(route('notifications.markAllAsRead'), {}, {
        preserveScroll: true,
    });
};

const deleteNotification = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus notifikasi ini?')) {
        router.delete(route('notifications.destroy', id), {
            preserveScroll: true,
        });
    }
};

const getNotificationIcon = (type) => {
    switch (type) {
        case 'go_boost_mention':
            return 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z';
        case 'go_boost_perbaikan':
            return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
        default:
            return 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9';
    }
};

const getNotificationColor = (type) => {
    switch (type) {
        case 'go_boost_mention':
            return 'bg-blue-100 text-blue-800';
        case 'go_boost_perbaikan':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

// State untuk form perbaikan
const showPerbaikanForm = ref({});
const perbaikanForms = ref({});

const togglePerbaikanForm = (goBoostId) => {
    if (!showPerbaikanForm.value[goBoostId]) {
        showPerbaikanForm.value[goBoostId] = true;
        perbaikanForms.value[goBoostId] = useForm({
            keterangan_perbaikan: '',
            foto_perbaikan: [],
        });
    } else {
        showPerbaikanForm.value[goBoostId] = false;
    }
};

const fotoPerbaikanPreviews = ref({});
const maxFiles = 5;
const fileInputRefs = ref({});

const handleFotoPerbaikanChange = (event, goBoostId) => {
    const files = Array.from(event.target.files || []);
    const currentCount = perbaikanForms.value[goBoostId]?.foto_perbaikan?.length || 0;
    const totalCount = currentCount + files.length;

    if (totalCount > maxFiles) {
        alert(`Maksimal ${maxFiles} foto. Anda telah memilih ${totalCount} foto.`);
        event.target.value = '';
        return;
    }

    const validFiles = [];
    for (const file of files) {
        if (file.size > 2 * 1024 * 1024) {
            alert(`File "${file.name}" melebihi 2MB. File diabaikan.`);
            continue;
        }
        validFiles.push(file);
    }

    if (!perbaikanForms.value[goBoostId]) {
        perbaikanForms.value[goBoostId] = useForm({
            keterangan_perbaikan: '',
            foto_perbaikan: [],
        });
    }

    perbaikanForms.value[goBoostId].foto_perbaikan = [
        ...(perbaikanForms.value[goBoostId].foto_perbaikan || []),
        ...validFiles,
    ];

    if (!fotoPerbaikanPreviews.value[goBoostId]) {
        fotoPerbaikanPreviews.value[goBoostId] = [];
    }

    validFiles.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            fotoPerbaikanPreviews.value[goBoostId].push({
                id: Date.now() + Math.random(),
                url: e.target.result,
                file: file,
            });
        };
        reader.readAsDataURL(file);
    });

    event.target.value = '';
};

const removeFotoPerbaikan = (goBoostId, previewId) => {
    const index = fotoPerbaikanPreviews.value[goBoostId]?.findIndex((p) => p.id === previewId);
    if (index !== -1) {
        const preview = fotoPerbaikanPreviews.value[goBoostId][index];
        perbaikanForms.value[goBoostId].foto_perbaikan = perbaikanForms.value[goBoostId].foto_perbaikan.filter(
            (f) => f !== preview.file
        );
        fotoPerbaikanPreviews.value[goBoostId].splice(index, 1);
    }
};

const submitPerbaikan = (goBoostId) => {
    const form = perbaikanForms.value[goBoostId];
    if (!form) return;

    form.post(route('go_boost.submitPerbaikan', goBoostId), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showPerbaikanForm.value[goBoostId] = false;
            fotoPerbaikanPreviews.value[goBoostId] = [];
        },
    });
};
</script>

<template>
    <Head title="Notifikasi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900">
                    Notifikasi
                </h2>
                <div class="flex items-center gap-3">
                    <PrimaryButton
                        v-if="notifications.data.some(n => !n.is_read)"
                        @click="markAllAsRead"
                        class="text-sm"
                    >
                        Tandai Semua Dibaca
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl">
                <!-- Empty State -->
                <div
                    v-if="notifications.data.length === 0"
                    class="rounded-2xl bg-white/90 p-12 text-center shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60"
                >
                    <svg
                        class="mx-auto h-16 w-16 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                        />
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">
                        Belum ada notifikasi
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Notifikasi akan muncul di sini ketika Anda di-mention atau ada aktivitas terkait.
                    </p>
                </div>

                <!-- Notifications List -->
                <div v-else class="space-y-4">
                    <div
                        v-for="notification in notifications.data"
                        :key="notification.id"
                        :class="[
                            'rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60 transition duration-200 hover:shadow-2xl hover:shadow-gray-300/60',
                            !notification.is_read ? 'border-l-4' : '',
                            !notification.is_read ? 'border-blue-500' : '',
                        ]"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div
                                :class="[
                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                                    getNotificationColor(notification.type),
                                ]"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="getNotificationIcon(notification.type)"
                                    />
                                </svg>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <h3
                                            :class="[
                                                'text-base font-semibold',
                                                !notification.is_read ? 'text-gray-900' : 'text-gray-700',
                                            ]"
                                        >
                                            {{ notification.title }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ notification.message }}
                                        </p>
                                        <div
                                            v-if="notification.go_boost"
                                            class="mt-3 rounded-lg bg-blue-50 p-3 border border-blue-100"
                                        >
                                            <p class="text-xs font-medium text-blue-900 mb-1">
                                                GO BOOST Details:
                                            </p>
                                            <p class="text-xs text-blue-700 mb-2">
                                                Area: {{ notification.go_boost.area_temuan }} | 
                                                Ruangan: {{ notification.go_boost.ruangan_temuan }} | 
                                                Oleh: {{ notification.go_boost.user_name }}
                                            </p>
                                            <p class="text-xs text-blue-700 mb-2">
                                                <strong>Temuan:</strong> {{ notification.go_boost.penjelasan_temuan }}
                                            </p>
                                            
                                            <!-- Status Perbaikan -->
                                            <div v-if="notification.go_boost.has_perbaikan" class="mt-3 rounded-lg bg-blue-50 p-3 border border-blue-200">
                                                <p class="text-xs font-medium text-blue-900 mb-1">
                                                    ✅ Perbaikan Selesai
                                                </p>
                                                <p class="text-xs text-blue-700 mb-2">
                                                    <strong>Keterangan:</strong> {{ notification.go_boost.keterangan_perbaikan }}
                                                </p>
                                                <p class="text-xs text-blue-600 mb-2" v-if="notification.go_boost.tanggal_perbaikan">
                                                    Selesai pada: {{ notification.go_boost.tanggal_perbaikan }}
                                                </p>
                                                <div v-if="notification.go_boost.foto_perbaikan && notification.go_boost.foto_perbaikan.length > 0" class="grid grid-cols-2 gap-2 mt-2">
                                                    <div
                                                        v-for="(foto, index) in notification.go_boost.foto_perbaikan"
                                                        :key="index"
                                                        class="relative"
                                                    >
                                                        <img
                                                            :src="'/storage/' + foto"
                                                            :alt="`Foto perbaikan ${index + 1}`"
                                                            class="w-full h-24 object-cover rounded-lg border border-blue-200"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Form Perbaikan (hanya untuk user yang di-mention dan belum ada perbaikan) -->
                                            <div v-else-if="notification.go_boost.is_mentioned" class="mt-3">
                                                <button
                                                    v-if="!showPerbaikanForm[notification.go_boost.id]"
                                                    @click="togglePerbaikanForm(notification.go_boost.id)"
                                                    class="w-full rounded-lg bg-[#00529b] px-4 py-2 text-xs font-semibold text-white hover:bg-[#004080] transition-colors"
                                                >
                                                    📝 Lakukan Perbaikan
                                                </button>
                                                
                                                <!-- Form Perbaikan -->
                                                <div
                                                    v-if="showPerbaikanForm[notification.go_boost.id]"
                                                    class="mt-3 rounded-lg bg-white p-4 border border-blue-200"
                                                >
                                                    <h4 class="text-sm font-semibold text-gray-900 mb-3">
                                                        Form Perbaikan GO BOOST
                                                    </h4>
                                                    
                                                    <form @submit.prevent="submitPerbaikan(notification.go_boost.id)">
                                                        <!-- Keterangan Perbaikan -->
                                                        <div class="mb-4">
                                                            <InputLabel for="keterangan_perbaikan" value="Keterangan Perbaikan *" />
                                                            <textarea
                                                                id="keterangan_perbaikan"
                                                                v-model="perbaikanForms[notification.go_boost.id].keterangan_perbaikan"
                                                                rows="4"
                                                                class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-3 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                                                                required
                                                                placeholder="Jelaskan perbaikan yang telah dilakukan..."
                                                            ></textarea>
                                                            <InputError class="mt-2" :message="perbaikanForms[notification.go_boost.id]?.errors?.keterangan_perbaikan" />
                                                        </div>
                                                        
                                                        <!-- Upload Foto Perbaikan -->
                                                        <div class="mb-4">
                                                            <InputLabel for="foto_perbaikan" value="Foto Perbaikan (Opsional)" />
                                                            <input
                                                                :id="`foto_perbaikan_${notification.go_boost.id}`"
                                                                :ref="el => fileInputRefs[notification.go_boost.id] = el"
                                                                type="file"
                                                                accept="image/*"
                                                                capture="environment"
                                                                multiple
                                                                @change="handleFotoPerbaikanChange($event, notification.go_boost.id)"
                                                                class="hidden"
                                                            />
                                                            <button
                                                                type="button"
                                                                @click="fileInputRefs[notification.go_boost.id]?.click()"
                                                                :disabled="(fotoPerbaikanPreviews[notification.go_boost.id]?.length || 0) >= maxFiles"
                                                                class="mt-2 w-full inline-flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-[#00529b]/40 bg-blue-50 px-4 py-3 text-sm font-semibold text-[#00529b] hover:bg-blue-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:ring-2 focus:ring-[#00529b] focus:ring-offset-2"
                                                            >
                                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                </svg>
                                                                📷 Ambil Foto Perbaikan
                                                            </button>
                                                            <p class="mt-2 text-xs text-gray-500">
                                                                Maksimal {{ maxFiles }} foto @ 2MB per foto
                                                            </p>
                                                            <div v-if="fotoPerbaikanPreviews[notification.go_boost.id]?.length > 0" class="mt-2 grid grid-cols-2 gap-2">
                                                                <div
                                                                    v-for="preview in fotoPerbaikanPreviews[notification.go_boost.id]"
                                                                    :key="preview.id"
                                                                    class="relative"
                                                                >
                                                                    <button
                                                                        type="button"
                                                                        @click="removeFotoPerbaikan(notification.go_boost.id, preview.id)"
                                                                        class="absolute -right-2 -top-2 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white text-xs hover:bg-red-600"
                                                                    >
                                                                        ×
                                                                    </button>
                                                                    <img
                                                                        :src="preview.url"
                                                                        :alt="`Preview foto perbaikan`"
class="w-full h-24 object-cover rounded-lg border border-blue-200"
                                                                        />
                                                                    </div>
                                                                </div>
                                                            <InputError class="mt-2" :message="perbaikanForms[notification.go_boost.id]?.errors?.foto_perbaikan" />
                                                        </div>
                                                        
                                                        <!-- Action Buttons -->
                                                        <div class="flex items-center gap-2">
                                                            <button
                                                                type="button"
                                                                @click="togglePerbaikanForm(notification.go_boost.id)"
                                                                class="flex-1 rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 transition-colors"
                                                            >
                                                                Batal
                                                            </button>
                                                            <PrimaryButton
                                                                type="submit"
                                                                :disabled="perbaikanForms[notification.go_boost.id]?.processing"
                                                                class="flex-1"
                                                            >
                                                                <span v-if="perbaikanForms[notification.go_boost.id]?.processing">Menyimpan...</span>
                                                                <span v-else>✅ Submit Perbaikan</span>
                                                            </PrimaryButton>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            <Link
                                                :href="route('go_boost.index')"
                                                class="mt-2 inline-block text-xs font-medium text-blue-600 hover:text-blue-800"
                                            >
                                                Lihat Detail GO BOOST →
                                            </Link>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500">
                                            {{ notification.created_at_human }}
                                        </p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="!notification.is_read"
                                            @click="markAsRead(notification.id)"
                                            class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 transition-colors"
                                            title="Tandai sebagai dibaca"
                                        >
                                            Tandai Dibaca
                                        </button>
                                        <button
                                            @click="deleteNotification(notification.id)"
                                            class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 transition-colors"
                                            title="Hapus notifikasi"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="notifications.links && notifications.links.length > 3"
                    class="mt-6 flex items-center justify-center gap-2"
                >
                    <template v-for="(link, index) in notifications.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-white text-gray-700 hover:bg-gray-50',
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-4 py-2 text-sm text-gray-400"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

