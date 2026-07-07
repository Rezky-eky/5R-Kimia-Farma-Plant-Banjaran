<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PhotoImagePicker from '@/Components/PhotoImagePicker.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import PhotoGallery from '@/Components/PhotoGallery.vue';

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
        case 'go_check_solver_needed':
        case 'go_check_perbaikan':
            return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'go_check_approved_finder':
        case 'go_check_approved_solver':
            return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'go_check_schedule':
            return 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z';
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
        case 'go_offer_request':
        case 'go_sale_request':
            return 'bg-blue-100 text-blue-800';
        case 'go_offer_request_sent':
            return 'bg-emerald-100 text-emerald-800';
        case 'go_offer_approved':
        case 'go_sale_approved':
            return 'bg-green-100 text-green-800';
        case 'go_offer_rejected':
        case 'go_sale_rejected':
        case 'go_care_rejected':
        case 'go_boost_rejected_finder':
        case 'go_boost_rejected_solver':
        case 'go_check_rejected':
            return 'bg-red-100 text-red-800';
        case 'go_check_schedule':
            return 'bg-amber-100 text-amber-800';
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

const maxFiles = 5;

const submitPerbaikan = (goBoostId) => {
    const form = perbaikanForms.value[goBoostId];
    if (!form) return;

    form.post(route('go_boost.submitPerbaikan', goBoostId), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showPerbaikanForm.value[goBoostId] = false;
        },
    });
};

const gcKey = (id) => `gc_${id}`;

const toggleGoCheckPerbaikanForm = (goCheckId) => {
    const key = gcKey(goCheckId);
    if (!showPerbaikanForm.value[key]) {
        showPerbaikanForm.value[key] = true;
        perbaikanForms.value[key] = useForm({
            keterangan_perbaikan: '',
            foto_perbaikan: [],
        });
    } else {
        showPerbaikanForm.value[key] = false;
    }
};

const submitGoCheckPerbaikan = (goCheckId) => {
    const key = gcKey(goCheckId);
    const form = perbaikanForms.value[key];
    if (!form) return;

    form.post(route('go_check.submitPerbaikan', goCheckId), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showPerbaikanForm.value[key] = false;
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
                <div class="flex flex-wrap items-center gap-3">
                    <BackToDashboard />
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

        <div class="py-8 overflow-x-hidden">
            <div class="mx-auto max-w-4xl min-w-0 px-4 sm:px-6">
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
                            'min-w-0 overflow-hidden rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60 transition duration-200 hover:shadow-2xl hover:shadow-gray-300/60',
                            !notification.is_read ? 'border-l-4' : '',
                            !notification.is_read ? 'border-blue-500' : '',
                        ]"
                    >
                        <div class="flex min-w-0 items-start gap-4">
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
                                <div class="flex min-w-0 items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1 overflow-hidden">
                                        <h3
                                            :class="[
                                                'text-base font-semibold break-words break-all',
                                                !notification.is_read ? 'text-gray-900' : 'text-gray-700',
                                            ]"
                                        >
                                            {{ notification.title }}
                                        </h3>
                                        <p
                                            v-if="notification.type !== 'go_check_schedule'"
                                            class="mt-1 text-sm text-gray-600 break-words break-all whitespace-pre-wrap"
                                        >
                                            {{ notification.message }}
                                        </p>
                                        <div
                                            v-if="notification.type === 'go_check_schedule'"
                                            class="mt-3 min-w-0 overflow-hidden rounded-lg bg-amber-50 p-4 border border-amber-100"
                                        >
                                            <p class="text-sm font-semibold text-amber-900">
                                                Anda dijadwalkan Go Check:
                                            </p>
                                            <dl class="mt-2 space-y-1.5 text-sm text-amber-800">
                                                <div>
                                                    <dt class="inline font-medium">Tim:</dt>
                                                    <dd class="inline ml-1">
                                                        {{ notification.go_check_schedule?.team_name || '—' }}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt class="inline font-medium">Pada:</dt>
                                                    <dd class="inline ml-1">
                                                        {{ notification.go_check_schedule?.scheduled_date || '—' }}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt class="inline font-medium">Area Go Check:</dt>
                                                    <dd class="inline ml-1 break-words break-all">
                                                        {{ notification.go_check_schedule?.target_area || '—' }}
                                                    </dd>
                                                </div>
                                            </dl>
                                            <Link
                                                v-if="$page.props.auth?.user?.can_go_check_finder"
                                                :href="route('go_check.create')"
                                                class="mt-4 inline-flex items-center rounded-lg bg-[#00529b] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#004080] transition-colors"
                                            >
                                                Laporan Go Check
                                            </Link>
                                        </div>
                                        <div
                                            v-if="notification.go_boost"
                                            class="mt-3 min-w-0 overflow-hidden rounded-lg bg-blue-50 p-3 border border-blue-100"
                                        >
                                            <p class="text-xs font-medium text-blue-900 mb-1 break-words">
                                                GO BOOST Details:
                                            </p>
                                            <p class="text-xs text-blue-700 mb-2 break-words break-all">
                                                Area: {{ notification.go_boost.area_temuan }} | 
                                                Ruangan: {{ notification.go_boost.ruangan_temuan }} | 
                                                Oleh: {{ notification.go_boost.user_name }}
                                            </p>
                                            <p class="text-xs text-blue-700 mb-2 break-words break-all whitespace-pre-wrap">
                                                <strong>Temuan:</strong> {{ notification.go_boost.penjelasan_temuan }}
                                            </p>
                                            
                                            <!-- Status Perbaikan -->
                                            <div v-if="notification.go_boost.has_perbaikan" class="mt-3 rounded-lg bg-blue-50 p-3 border border-blue-200">
                                                <p class="text-xs font-medium text-blue-900 mb-1">
                                                    ✅ Perbaikan Selesai
                                                </p>
                                                <p class="text-xs text-blue-700 mb-2 break-words break-all whitespace-pre-wrap">
                                                    <strong>Keterangan:</strong> {{ notification.go_boost.keterangan_perbaikan }}
                                                </p>
                                                <p class="text-xs text-blue-600 mb-2" v-if="notification.go_boost.tanggal_perbaikan">
                                                    Selesai pada: {{ notification.go_boost.tanggal_perbaikan }}
                                                </p>
                                                <div v-if="notification.go_boost.foto_perbaikan?.length" class="mt-2">
                                                    <PhotoGallery
                                                        :images="notification.go_boost.foto_perbaikan.map((f) => (String(f).startsWith('http') ? f : `/storage/${f}`))"
                                                        title="Foto perbaikan"
                                                        grid-class="grid-cols-2"
                                                        thumbnail-height-class="h-24"
                                                    />
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
                                                        <div v-if="perbaikanForms[notification.go_boost.id]" class="mb-4">
                                                            <InputLabel value="Foto Perbaikan (Opsional)" />
                                                            <PhotoImagePicker
                                                                v-model="perbaikanForms[notification.go_boost.id].foto_perbaikan"
                                                                :input-id="`foto-perbaikan-${notification.go_boost.id}`"
                                                                :max-files="maxFiles"
                                                                label=""
                                                                hint="Maksimal 5 foto @ 10MB. Ambil foto atau pilih dari galeri."
                                                            >
                                                                <InputError class="mt-2" :message="perbaikanForms[notification.go_boost.id]?.errors?.foto_perbaikan" />
                                                            </PhotoImagePicker>
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

                                        <div
                                            v-if="notification.go_check"
                                            class="mt-3 min-w-0 overflow-hidden rounded-lg bg-teal-50 p-3 border border-teal-100"
                                        >
                                            <p class="text-xs font-medium text-teal-900 mb-1 break-words break-all">
                                                GO CHECK — Bagian {{ notification.go_check.bagian }}
                                            </p>
                                            <p class="text-xs text-teal-800 break-words break-all">
                                                {{ notification.go_check.area_temuan }} / {{ notification.go_check.ruangan_temuan }}
                                                · Finder: {{ notification.go_check.finder_name }}
                                            </p>
                                            <p class="text-xs text-teal-700 mt-1 break-words break-all whitespace-pre-wrap">
                                                {{ notification.go_check.penjelasan_temuan }}
                                            </p>
                                            <p
                                                v-if="notification.go_check.has_perbaikan"
                                                class="mt-2 text-xs text-teal-800 break-words break-all whitespace-pre-wrap"
                                            >
                                                <strong>Solver:</strong> {{ notification.go_check.keterangan_perbaikan }}
                                            </p>
                                            <div v-else-if="notification.go_check.can_submit_solver" class="mt-3">
                                                <button
                                                    v-if="!showPerbaikanForm[gcKey(notification.go_check.id)]"
                                                    type="button"
                                                    class="w-full rounded-lg bg-teal-700 px-4 py-2 text-xs font-semibold text-white hover:bg-teal-800"
                                                    @click="toggleGoCheckPerbaikanForm(notification.go_check.id)"
                                                >
                                                    Input Perbaikan (Solver)
                                                </button>
                                                <form
                                                    v-if="showPerbaikanForm[gcKey(notification.go_check.id)]"
                                                    class="mt-2 space-y-2"
                                                    @submit.prevent="submitGoCheckPerbaikan(notification.go_check.id)"
                                                >
                                                    <textarea
                                                        v-model="perbaikanForms[gcKey(notification.go_check.id)].keterangan_perbaikan"
                                                        rows="3"
                                                        required
                                                        class="w-full rounded-lg text-sm border-gray-200"
                                                        placeholder="Keterangan perbaikan bagian Anda..."
                                                    />
                                                    <PhotoImagePicker
                                                        v-if="perbaikanForms[gcKey(notification.go_check.id)]"
                                                        v-model="perbaikanForms[gcKey(notification.go_check.id)].foto_perbaikan"
                                                        :input-id="`gc-foto-${notification.go_check.id}`"
                                                        :max-files="maxFiles"
                                                        label="Foto perbaikan (opsional)"
                                                        hint="Maksimal 5 foto @ 10MB. Ambil foto atau pilih dari galeri."
                                                    />
                                                    <PrimaryButton type="submit" class="w-full">Submit Solver</PrimaryButton>
                                                </form>
                                            </div>
                                        </div>

                                        <p class="mt-2 text-xs text-gray-500">
                                            {{ notification.created_at_human }}
                                        </p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex shrink-0 items-center gap-2">
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

                <div class="mt-6 overflow-hidden rounded-xl bg-white shadow ring-1 ring-gray-100">
                    <PaginationBar :paginator="notifications" item-label="notifikasi" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

