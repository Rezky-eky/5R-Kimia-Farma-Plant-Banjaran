<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

// Ambil props Inertia
const page = usePage();

// Computed untuk cek apakah user adalah admin dan apakah user boleh melihat data admin
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');
const canViewAdminData = computed(() => !!page.props.auth?.user?.can_view_admin_data);
const canManageGoCheck = computed(() => !!page.props.auth?.user?.can_manage_go_check);
const canGoCheckFinder = computed(() => !!page.props.auth?.user?.can_go_check_finder);

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

// Kunci scroll body hanya saat drawer mobile terbuka; selalu reset saat navigasi/unmount
const unlockBodyScroll = () => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = '';
    document.body.style.position = '';
    document.body.style.width = '';
};

watch(showAdminSidebar, (isOpen) => {
    if (typeof document === 'undefined') return;
    if (isOpen && window.innerWidth < 1024) {
        document.body.style.overflow = 'hidden';
    } else {
        unlockBodyScroll();
    }
});

router.on('finish', () => {
    showAdminSidebar.value = false;
    unlockBodyScroll();
});

onUnmounted(unlockBodyScroll);

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
                name="toast"
            >
                <div
                    v-if="flashSuccess && showSuccessNotification"
                    class="fixed top-4 right-4 z-50 max-w-sm w-full sm:w-auto"
                >
                    <div class="motion-pulse rounded-xl text-white shadow-lg ring-1 ring-slate-300/20 p-4 flex items-start justify-between gap-4 backdrop-blur" style="background-color: #6689a3; box-shadow: 0 12px 28px rgba(71, 85, 105, 0.16);">
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
                name="toast"
            >
                <div
                    v-if="flashError && showErrorNotification"
                    class="fixed top-4 right-4 z-50 max-w-sm w-full sm:w-auto sm:top-20"
                >
                    <div class="rounded-xl bg-rose-500 text-white shadow-lg ring-1 ring-rose-600/20 p-4 flex items-start justify-between gap-4">
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
                name="toast"
            >
                <div
                    v-if="flashSuccess && showSuccessNotification"
                    class="fixed top-4 left-4 right-4 z-50 sm:hidden"
                >
                    <div class="motion-pulse rounded-xl text-white shadow-lg ring-1 ring-slate-300/20 p-4 flex items-start justify-between gap-3 backdrop-blur" style="background-color: #6689a3; box-shadow: 0 12px 28px rgba(71, 85, 105, 0.16);">
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
            <nav class="sticky top-0 z-40 border-b border-[#e5e9e5] bg-[#fffdfa]/95 shadow-[0_4px_18px_rgba(93,108,101,0.07)] backdrop-blur">
                <div class="px-3 sm:px-6 lg:px-8">
                    <div class="flex min-h-14 items-center justify-between py-1">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="showAdminSidebar = !showAdminSidebar"
                                class="min-h-[42px] min-w-[42px] rounded-lg p-2 text-[#71817f] hover:bg-[#edf3f0] lg:hidden"
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
                            <Link :href="route('notifications.index')" class="relative min-h-[42px] min-w-[42px] rounded-lg p-2 text-[#71817f] hover:bg-[#edf3f0]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span v-if="$page.props.unreadNotificationsCount > 0" class="absolute top-0.5 right-0.5 h-4 w-4 rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center">{{ $page.props.unreadNotificationsCount > 99 ? '99+' : $page.props.unreadNotificationsCount }}</span>
                            </Link>
                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button type="button" class="flex items-center gap-2 p-1.5 rounded-lg text-gray-700 hover:bg-gray-100">
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center text-white text-sm font-semibold" style="background: #86a7a0;">{{ $page.props.auth.user.name.charAt(0).toUpperCase() }}</div>
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
                    class="fixed inset-0 bg-slate-900/25 backdrop-blur-[1px] z-20 lg:hidden"
                    aria-hidden="true"
                    @click="showAdminSidebar = false"
                />

                <!-- Sidebar: desktop (in-flow + sticky), mobile (fixed drawer) -->
                <aside
                    class="z-30 flex flex-col bg-[#f4f8f5] border-r border-[#e0e8e3] shadow-[4px_0_18px_rgba(93,108,101,0.06)]
                           fixed lg:sticky top-14 lg:top-14 left-0
                           h-[calc(100dvh-3.5rem)] lg:h-[calc(100dvh-3.5rem)] max-h-[calc(100dvh-3.5rem)] pb-[env(safe-area-inset-bottom)]
                           w-72 sm:w-80 lg:w-60
                           transform transition-transform duration-300 ease-out
                           lg:translate-x-0"
                    :class="showAdminSidebar ? 'translate-x-0' : '-translate-x-full'"
                    aria-label="Sidebar navigasi"
                >
                    <nav class="flex-1 min-h-0 w-full overflow-y-auto overscroll-contain px-3 py-3 pb-6">
                        <NavLink v-if="canViewAdminData" :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            Data 5R
                        </NavLink>
                        <NavLink :href="route('dashboard')" :active="route().current('dashboard') && !route().current('admin.*') && !route().current('go_check.management.*')">
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
                        <NavLink v-if="canManageGoCheck" :href="route('go_check.management.dashboard')" :active="route().current('go_check.management.*')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                            Kelola Go Check
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
                        <NavLink v-if="canGoCheckFinder" :href="route('go_check.create')" :active="route().current('go_check.create')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            Go Check
                        </NavLink>
                        <NavLink :href="route('go_offer.index')" :active="route().current('go_offer.*')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                            Go Offer
                        </NavLink>
                        <NavLink :href="route('go_sale.index')" :active="route().current('go_sale.*')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Go Sale
                        </NavLink>
                        <NavLink :href="route('leaderboard')" :active="route().current('leaderboard')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19h16M6 16V8m6 8V4m6 12v-5" /></svg>
                            Leaderboard 5R
                        </NavLink>
                        <NavLink v-if="canViewAdminData && isAdmin" :href="route('admin.go_reward')" :active="route().current('admin.go_reward')">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                            Go Reward
                        </NavLink>
                    </nav>
                </aside>

                <div
                    class="flex-1 min-w-0 transition-[transform,filter,opacity] duration-200 lg:transition-none"
                    :class="showAdminSidebar ? 'max-lg:opacity-70 max-lg:scale-[0.985] max-lg:brightness-75 max-lg:pointer-events-none' : ''"
                >
                    <Transition name="page" mode="out-in" appear>
                        <div :key="$page.component">
                            <header v-if="$slots.header" class="border-b border-[#e5e9e5] bg-[#fffdfa]">
                                <div class="px-4 py-3 sm:px-6 lg:px-8"><slot name="header" /></div>
                            </header>
                            <main class="min-h-[calc(100vh-8rem)] overflow-x-hidden bg-[#f7f8f6] pb-6 pt-3 touch-pan-y">
                                <div class="px-3 sm:px-6 lg:px-8"><slot /></div>
                            </main>
                        </div>
                    </Transition>
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
