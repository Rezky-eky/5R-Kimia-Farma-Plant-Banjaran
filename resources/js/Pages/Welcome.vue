<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="5R Kimia Farma Plant Banjaran - Sistem Manajemen 5R" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100">
        <!-- Header Premium -->
        <header class="w-full bg-white/95 backdrop-blur-lg shadow-sm border-b border-gray-200/60 sticky top-0 z-50">
            <div class="mx-auto max-w-7xl px-4 py-2 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <!-- Logo & Brand -->
                    <div class="flex items-center space-x-3">
                        <ApplicationLogo class="h-10 w-auto" />
                    </div>

                    <!-- Navigation -->
                    <nav v-if="canLogin" class="flex items-center space-x-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white rounded-xl shadow-md transition-all duration-200 hover:shadow-lg hover:scale-[1.02]"
                            style="background-color: #00529b;"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center px-6 py-3 text-sm font-bold rounded-xl text-white shadow-lg shadow-[#00529b]/30 ring-2 ring-[#00529b]/25 transition-all duration-200 hover:shadow-2xl hover:scale-[1.02] hover:bg-[#004080]"
                                style="background-color: #00529b; border-color: rgba(0, 82, 155, 0.35);"
                            >
                                Login
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white rounded-xl shadow-md transition-all duration-200 hover:shadow-lg hover:scale-[1.02]"
                                style="background-color: #00529b;"
                            >
                                Daftar
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Hero Section Premium -->
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center text-center">
                <!-- Hero Card Premium -->
                <div class="w-full max-w-5xl rounded-3xl bg-white p-12 md:p-16 mb-20 shadow-2xl ring-1 ring-gray-200/50" style="box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.12);">
                    <!-- Logo & Branding -->
                    <div class="mb-10">
                        <div class="flex justify-center mb-6">
                            <ApplicationLogo class="h-20 w-auto" />
                        </div>
                        <h1 class="text-5xl md:text-6xl font-extrabold mb-4 tracking-tight" style="color: #00529b;">
                            Sistem Manajemen 5R
                        </h1>
                        <p class="text-xl md:text-2xl font-semibold text-gray-700 mb-3">
                            5R Kimia Farma Plant Banjaran
                        </p>
                        <div class="inline-flex items-center px-4 py-2 rounded-full bg-slate-50 border border-gray-200">
                            <p class="text-sm font-medium italic" style="color: #00529b;">
                                Berdaya
                            </p>
                            <span class="mx-2 text-gray-400">•</span>
                            <p class="text-xs text-gray-600">
                                Bersih dalam bekerja, amanah dalam berkarya
                            </p>
                        </div>
                    </div>

                    <p class="max-w-3xl mx-auto text-lg md:text-xl text-gray-600 leading-relaxed mb-10">
                        Solusi manajemen terintegrasi untuk menciptakan lingkungan
                        kerja yang produktif, efisien, dan aman melalui praktik 5R
                    </p>

                    <!-- CTA Button Premium -->
                    <div class="mt-8">
                        <Link
                            v-if="canLogin && !$page.props.auth.user"
                            :href="route('login')"
                            class="inline-flex items-center px-10 py-4 text-lg font-semibold text-white rounded-xl shadow-xl transition-all duration-200 hover:shadow-2xl hover:scale-[1.02]"
                            style="background-color: #00529b; box-shadow: 0 20px 25px -5px rgba(0, 82, 155, 0.4), 0 10px 10px -5px rgba(0, 82, 155, 0.3);"
                        >
                            Mulai
                            <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- 5R Pilar Cards -->
                <div class="w-full mb-20">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-12">Lima Pilar 5R</h2>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                        <!-- Ringkas -->
                        <div class="group rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-200/50 transition-all duration-300 hover:translate-y-[-6px] hover:shadow-xl">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl mb-4 transition group-hover:scale-110" style="background-color: rgba(220, 38, 38, 0.1);">
                                <svg class="h-8 w-8" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Seiri (Ringkas)</h3>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Pisahkan barang yang diperlukan dan tidak diperlukan di tempat kerja
                            </p>
                        </div>

                        <!-- Rapi -->
                        <div class="group rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-200/50 transition-all duration-300 hover:translate-y-[-6px] hover:shadow-xl">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl mb-4 transition group-hover:scale-110" style="background-color: rgba(0, 82, 155, 0.1);">
                                <svg class="h-8 w-8" style="color: #00529b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Seiton (Rapi)</h3>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Atur barang dengan rapi agar mudah dicari dan digunakan
                            </p>
                        </div>

                        <!-- Resik -->
                        <div class="group rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-200/50 transition-all duration-300 hover:translate-y-[-6px] hover:shadow-xl">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl mb-4 transition group-hover:scale-110" style="background-color: rgba(249, 115, 22, 0.1);">
                                <svg class="h-8 w-8" style="color: #f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Seiso (Resik)</h3>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Bersihkan tempat kerja secara rutin dan teratur
                            </p>
                        </div>

                        <!-- Rawat -->
                        <div class="group rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-200/50 transition-all duration-300 hover:translate-y-[-6px] hover:shadow-xl">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl mb-4 transition group-hover:scale-110" style="background-color: rgba(22, 163, 74, 0.1);">
                                <svg class="h-8 w-8" style="color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Seiketsu (Rawat)</h3>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Standarisasi dan rawat kondisi bersih yang telah dicapai
                            </p>
                        </div>

                        <!-- Rajin -->
                        <div class="group rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-200/50 transition-all duration-300 hover:translate-y-[-6px] hover:shadow-xl">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl mb-4 transition group-hover:scale-110" style="background-color: rgba(6, 182, 212, 0.1);">
                                <svg class="h-8 w-8" style="color: #06b6d4;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Shitsuke (Rajin)</h3>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Audit teratur untuk menjaga standar 5R
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Manfaat Section -->
                <div class="w-full rounded-3xl bg-white p-10 md:p-12 shadow-xl ring-1 ring-gray-200/50">
                    <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-10 text-center">
                        Manfaat Penerapan 5R
                    </h3>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="(benefit, index) in [
                            { icon: 'M13 10V3L4 14h7v7l9-11h-7z', title: 'Peningkatan Produktivitas', desc: 'Temukan peralatan dengan cepat, kurangi waktu mencari' },
                            { icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', title: 'Peningkatan Keselamatan', desc: 'Lingkungan kerja lebih aman dan bersih' },
                            { icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', title: 'Pengurangan Biaya', desc: 'Kurangi waste dan tingkatkan efisiensi operasional' },
                            { icon: 'M17 20h5v-2a3 3 0 00-5.196-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.196-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', title: 'Kepuasan Karyawan', desc: 'Lingkungan kerja yang nyaman dan terorganisir' },
                            { icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', title: 'Kualitas Produk', desc: 'Konsistensi dan kualitas produk meningkat' },
                            { icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', title: 'Peningkatan Berkelanjutan', desc: 'Budaya perbaikan terus-menerus' }
                        ]" :key="index" class="flex items-start space-x-4 rounded-xl bg-slate-50 px-5 py-4 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex-shrink-0">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl" style="background-color: rgba(0, 82, 155, 0.1);">
                                    <svg class="h-6 w-6" style="color: #00529b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="benefit.icon" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-1">{{ benefit.title }}</h4>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ benefit.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer Premium -->
        <footer class="mt-6 border-t border-gray-200 bg-white py-4">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-base font-semibold italic mb-3" style="color: #00529b;">
                        Berdaya
                    </p>
                    <p class="text-sm text-gray-500 mb-2">
                        Bersih dalam bekerja, amanah dalam berkarya
                    </p>
                    <p class="text-xs text-gray-400">
                        5R Kimia Farma Plant Banjaran | Pengembangan Sistem Kimia Farma Plant Banjaran | RF
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
