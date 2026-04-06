<script setup>
import { ref, computed, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

// Ambil props Inertia
const page = usePage();

// Computed untuk cek apakah user adalah admin
const isAdmin = computed(() => {
    return page.props.auth?.user?.role === 'admin';
});

// State untuk Hamburger Menu
const showingNavigationDropdown = ref(false);
// State untuk sidebar admin (mobile: bisa collapse)
const showAdminSidebar = ref(false);

// (Toggle) tampilan fitur "Daftar Barang Ringkas (DBR)" tanpa menghapus fungsinya
const showDaftarBarangRingkasMenu = ref(true);

// State dan Logic untuk Notifikasi Flash Message
const showSuccessNotification = ref(false);
const showErrorNotification = ref(false);

// Computed untuk mendeteksi flash success message
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Watch flash success untuk menampilkan notifikasi
watch(flashSuccess, (newValue) => {
    if (newValue) {
        showSuccessNotification.value = true;
        setTimeout(() => {
            showSuccessNotification.value = false;
            if (page.props.flash) {
                page.props.flash.success = null;
            }
        }, 7000);
    }
}, { immediate: true });

watch(flashError, (newValue) => {
    if (newValue) {
        showErrorNotification.value = true;
        setTimeout(() => {
            showErrorNotification.value = false;
            if (page.props.flash) {
                page.props.flash.error = null;
            }
        }, 10000);
    }
}, { immediate: true });

// Saat sidebar drawer terbuka di mobile, kunci scroll background
watch(showAdminSidebar, (isOpen) => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = isOpen ? 'hidden' : '';
});

// Fungsi untuk menutup notifikasi secara manual
const closeNotification = () => {
    showSuccessNotification.value = false;
    if (page.props.flash) {
        page.props.flash.success = null;
    }
};

const closeErrorNotification = () => {
    showErrorNotification.value = false;
    if (page.props.flash) {
        page.props.flash.error = null;
    }
};
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-50">
            <!-- Notifikasi Success -->
            <Transition
                enter-active-class="transition ease-out duration-300 transform"
                enter-from-class="opacity-0 translate-x-full"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition ease-in duration-200 transform"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 translate-x-full"
            >
                <div
                    v-if="flashSuccess && showSuccessNotification"
                    class="fixed top-4 right-4 z-50 max-w-sm w-full sm:w-auto"
                >
                    <div class="rounded-xl text-white shadow-2xl ring-1 p-4 flex items-start justify-between gap-4 backdrop-blur" style="background-color: #00529b; box-shadow: 0 20px 25px -5px rgba(0, 82, 155, 0.4), 0 10px 10px -5px rgba(0, 82, 155, 0.3);">
                        <div class="flex items-start gap-3 flex-1">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm sm:text-base">{{ flashSuccess }}</p>
                            </div>
                        </div>
                        <button
                            @click="closeNotification"
                            class="flex-shrink-0 text-white/80 hover:text-white transition-colors duration-200 focus:outline-none"
                            aria-label="Tutup notifikasi"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Flash error (validasi / gagal simpan) -->
            <Transition
                enter-active-class="transition ease-out duration-300 transform"
                enter-from-class="opacity-0 translate-x-full"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition ease-in duration-200 transform"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 translate-x-full"
            >
                <div
                    v-if="flashError && showErrorNotification"
                    class="fixed top-4 right-4 z-50 max-w-sm w-full sm:w-auto sm:top-20"
                >
                    <div class="rounded-xl bg-red-600 text-white shadow-2xl ring-1 ring-red-700/30 p-4 flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3 flex-1 min-w-0">
                            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="font-medium text-sm sm:text-base break-words">{{ flashError }}</p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 text-white/80 hover:text-white"
                            aria-label="Tutup"
                            @click="closeErrorNotification"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Mobile Notifikasi -->
            <Transition
                enter-active-class="transition ease-out duration-300 transform"
                enter-from-class="opacity-0 -translate-y-full"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-200 transform"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-full"
            >
                <div
                    v-if="flashSuccess && showSuccessNotification"
                    class="fixed top-4 left-4 right-4 z-50 sm:hidden"
                >
                    <div class="rounded-xl text-white shadow-2xl ring-1 p-4 flex items-start justify-between gap-3 backdrop-blur" style="background-color: #00529b; box-shadow: 0 20px 25px -5px rgba(0, 82, 155, 0.4), 0 10px 10px -5px rgba(0, 82, 155, 0.3);">
                        <div class="flex items-start gap-2 flex-1">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="font-medium text-sm flex-1">{{ flashSuccess }}</p>
                        </div>
                        <button
                            @click="closeNotification"
                            class="flex-shrink-0 text-white/80 hover:text-white transition-colors duration-200 focus:outline-none"
                            aria-label="Tutup notifikasi"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Satu layout: Top bar + Sidebar untuk semua (admin & user) -->
            <!-- Top bar: logo, toggle sidebar, notif, user + poin -->
            <nav class="border-b border-gray-200 bg-white shadow-sm sticky top-0 z-40">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex h-14 justify-between items-center">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="showAdminSidebar = !showAdminSidebar"
                                class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100"
                                aria-label="Toggle sidebar"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <Link :href="route('dashboard')" class="flex items-center">
                                <ApplicationLogo class="h-9 w-auto" />
                            </Link>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link :href="route('notifications.index')" class="relative p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span v-if="$page.props.unreadNotificationsCount > 0" class="absolute top-0.5 right-0.5 h-4 w-4 rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center">{{ $page.props.unreadNotificationsCount > 99 ? '99+' : $page.props.unreadNotificationsCount }}</span>
                            </Link>
                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button type="button" class="flex items-center gap-2 p-1.5 rounded-lg text-gray-700 hover:bg-gray-100">
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center text-white text-sm font-semibold" style="background: linear-gradient(135deg, #00529b 0%, #003d75 100%);">{{ $page.props.auth.user.name.charAt(0).toUpperCase() }}</div>
                                            <span class="text-sm font-medium hidden sm:inline">{{ $page.props.auth.user.name }}</span>
                                            <span class="hidden sm:inline text-xs font-semibold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800" title="Poin 5R">{{ $page.props.auth.user.points_balance ?? 0 }} pt</span>
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="px-4 py-3 border-b border-gray-200">
                                            <p class="text-sm font-medium text-gray-900">{{ $page.props.auth.user.name }}</p>
                                            <p class="text-xs text-gray-500">NPP: {{ $page.props.auth.user.npp }}</p>
                                            <p class="text-xs font-semibold mt-1" style="color: #00529b;">{{ $page.props.auth.user.points_balance ?? 0 }} poin</p>
                                        </div>
                                        <DropdownLink :href="route('notifications.index')">Notifikasi</DropdownLink>
                                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600">Log Out</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            <div class="flex relative">
                <!-- Overlay hanya untuk mobile saat drawer terbuka -->
                <div
                    v-show="showAdminSidebar"
                    class="fixed inset-0 bg-black/45 z-20 lg:hidden"
                    aria-hidden="true"
                    @click="showAdminSidebar = false"
                />

                <!-- Sidebar: desktop (in-flow + sticky), mobile (fixed drawer) -->
                <aside
                    class="z-30 bg-gradient-to-b from-slate-50 to-white border-r border-slate-200/80 shadow-sm
                           fixed lg:sticky top-14 lg:top-14 left-0
                           h-[calc(100vh-3.5rem)] lg:h-[calc(100vh-3.5rem)]
                           w-72 sm:w-80 lg:w-60
                           transform transition-transform duration-200
                           lg:translate-x-0"
                    :class="showAdminSidebar ? 'translate-x-0' : '-translate-x-full'"
                    aria-label="Sidebar navigasi"
                >
                    <nav class="py-5 px-3 space-y-1 flex flex-col w-full">
                        <NavLink v-if="isAdmin" :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            Admin Dashboard
                        </NavLink>
                        <NavLink :href="route('dashboard')" :active="route().current('dashboard') && !route().current('admin.*')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            Ringkasan 5R
                        </NavLink>
                        <NavLink
                            v-if="showDaftarBarangRingkasMenu"
                            :href="route('go_action.dbr_index')"
                            :active="route().current('go_action.dbr_index')"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Data Barang Ringkas (DBR)
                        </NavLink>
                        <NavLink :href="route('go_action.create')" :active="route().current('go_action.create')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Go Action
                        </NavLink>
                        <NavLink :href="route('go_boost.create')" :active="route().current('go_boost.create')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                            Go Boost
                        </NavLink>
                        <NavLink :href="route('go_care.create')" :active="route().current('go_care.create')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                            Go Care
                        </NavLink>
                        <NavLink :href="route('go_offer.index')" :active="route().current('go_offer.*')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                            Go Offer
                        </NavLink>
                        <NavLink :href="route('go_sale.index')" :active="route().current('go_sale.*')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Go Sale
                        </NavLink>
                        <NavLink v-if="isAdmin" :href="route('admin.go_reward')" :active="route().current('admin.go_reward')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                            Go Reward
                        </NavLink>
                        <NavLink v-if="isAdmin" :href="route('admin.audit.index')" :active="route().current('admin.audit.*')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Laporan 5R Keseluruhan
                        </NavLink>
                    </nav>
                </aside>

                <div
                    class="flex-1 min-w-0 transition-[transform,filter,opacity] duration-200 lg:transition-none"
                    :class="showAdminSidebar ? 'lg:opacity-100 lg:scale-100 lg:filter-none opacity-70 scale-[0.985] brightness-75 pointer-events-none' : ''"
                >
                    <header v-if="$slots.header" class="bg-white border-b border-gray-200">
                        <div class="px-4 py-2 sm:px-6 lg:px-8"><slot name="header" /></div>
                    </header>
                    <main class="pb-4 pt-2 bg-slate-50 min-h-[calc(100vh-8rem)]">
                        <div class="px-4 sm:px-6 lg:px-8"><slot /></div>
                    </main>
                    <footer class="border-t border-gray-200 bg-white py-3">
                        <div class="px-4 sm:px-6 lg:px-8 text-center">
                            <p class="text-sm font-semibold italic mb-1" style="color: #00529b;">Berdaya</p>
                            <p class="text-xs text-gray-500">Bersih dalam bekerja, amanah dalam berkarya · 5R Kimia Farma Plant Banjaran | Pengembangan Sistem Kimia Farma Plant Banjaran | RF</p>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</template>
