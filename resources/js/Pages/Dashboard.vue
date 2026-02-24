<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    total_go_actions: {
        type: Number,
        default: 0,
    },
    total_go_boosts: {
        type: Number,
        default: 0,
    },
    total_go_cares: {
        type: Number,
        default: 0,
    },
    laporan_bulanan: {
        type: Array,
        default: () => [],
    },
});

const fallbackData = [
    { bulan: 'Jan', open: 12, closed: 7 },
    { bulan: 'Feb', open: 15, closed: 9 },
    { bulan: 'Mar', open: 11, closed: 13 },
    { bulan: 'Apr', open: 18, closed: 14 },
    { bulan: 'Mei', open: 16, closed: 17 },
    { bulan: 'Jun', open: 14, closed: 19 },
];

const chartData = computed(() =>
    props.laporan_bulanan && props.laporan_bulanan.length
        ? props.laporan_bulanan
        : fallbackData,
);

const maxValue = computed(() => {
    const { value } = chartData;
    if (!value.length) {
        return 1;
    }

    return Math.max(
        1,
        ...value.map((item) => Math.max(item.open ?? 0, item.closed ?? 0)),
    );
});

const paddingTop = 8;
const paddingBottom = 8;

const generatePoints = (key) => {
    const data = chartData.value;
    if (!data.length) {
        return [];
    }

    const stepX = data.length > 1 ? 100 / (data.length - 1) : 100;

    return data.map((item, index) => {
        const x = data.length > 1 ? index * stepX : 50;
        const ratio = (item[key] ?? 0) / maxValue.value;
        const y = 100 - paddingBottom - ratio * (100 - paddingTop - paddingBottom);

        return {
            x: Number(x.toFixed(2)),
            y: Number(Math.max(paddingTop, Math.min(100 - paddingBottom, y)).toFixed(2)),
            value: item[key] ?? 0,
            label: item.bulan,
        };
    });
};

const buildPath = (key) => {
    const pts = generatePoints(key);
    if (!pts.length) {
        return '';
    }

    return pts
        .map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`)
        .join(' ');
};

const openPoints = computed(() => generatePoints('open'));
const closedPoints = computed(() => generatePoints('closed'));
const openPath = computed(() => buildPath('open'));
const closedPath = computed(() => buildPath('closed'));

const gridLines = computed(() => {
    const lines = 4;
    return Array.from({ length: lines + 1 }, (_, idx) => {
        const ratio = idx / lines;
        const value = Math.round(maxValue.value * (1 - ratio));
        const y = paddingTop + ratio * (100 - paddingTop - paddingBottom);
        return { y: Number(y.toFixed(2)), value };
    });
});
</script>

<template>
    <Head title="Dashboard Kinerja 5R Kimia Farma Plant Banjaran" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-2xl font-bold leading-tight text-gray-900"
            >
                Dashboard Kinerja 5R Kimia Farma Plant Banjaran
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- Ringkasan Kunci -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Card 1: Total Temuan GO BOOST -->
                    <div class="rounded-2xl bg-gradient-to-br from-sky-50 via-white to-sky-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Temuan</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Total Temuan GO BOOST Bulan Ini</h4>
                            </div>
                            <span class="text-xl">🔎</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold" style="color: #00529b;">{{ total_go_boosts }}</span>
                            <span class="text-xs text-gray-500">temuan</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Sumber: laporan observasi dan audit area kerja.</p>
                    </div>

                    <!-- Card 2: Total Perbaikan GO CARE -->
                    <div class="rounded-2xl bg-gradient-to-br from-slate-50 via-white to-slate-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Perbaikan</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Total Perbaikan GO CARE Selesai</h4>
                            </div>
                            <span class="text-xl">🛠️</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold" style="color: #00529b;">{{ total_go_cares }}</span>
                            <span class="text-xs text-gray-500">perbaikan</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Sumber: tindak lanjut penyelesaian dan verifikasi lapangan.</p>
                    </div>

                    <!-- Card 3: Aksi Ringkas GO ACTION -->
                    <div class="rounded-2xl bg-gradient-to-br from-rose-50 via-white to-rose-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Aksi Ringkas</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Total Barang Dipilah/Dibuang GO ACTION</h4>
                            </div>
                            <span class="text-xl">🧹</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-red-700">{{ total_go_actions }}</span>
                            <span class="text-xs text-gray-500">aksi</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Sumber: kegiatan pemilahan, pembersihan, dan penertiban area.</p>
                    </div>

                    <!-- Card 4: Status Keseluruhan -->
                    <div class="rounded-2xl bg-gradient-to-br from-amber-50 via-white to-amber-100/60 p-6 shadow-xl shadow-gray-300/50 transition duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-300/60">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Status</p>
                                <h4 class="mt-1 text-base font-semibold text-gray-800">Status Keseluruhan 5R</h4>
                            </div>
                            <span class="text-xl">📊</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-yellow-600">92%</span>
                            <span class="text-xs text-gray-500">compliance</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Tingkat kepatuhan implementasi 5R.</p>
                    </div>
                </div>

                <!-- Tren Kinerja -->
                <div class="rounded-2xl bg-white/90 p-6 shadow-2xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Tren Kinerja 5R (6 Bulan Terakhir)</h3>
                            <p class="text-sm text-gray-500">Perbandingan jumlah temuan OPEN dan CLOSED dari laporan bulanan.</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                                <span class="h-2 w-6 rounded-full bg-rose-300"></span>
                                OPEN
                            </div>
                            <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                                <span class="h-2 w-6 rounded-full" style="background-color: #00529b;"></span>
                                CLOSED
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="relative">
                            <svg
                                class="h-72 w-full text-blue-200"
                                viewBox="0 0 100 100"
                                preserveAspectRatio="none"
                            >
                                <g v-for="line in gridLines" :key="line.y">
                                    <line
                                        :x1="0"
                                        :x2="100"
                                        :y1="line.y"
                                        :y2="line.y"
                                        stroke="currentColor"
                                        stroke-width="0.6"
                                        stroke-dasharray="4 4"
                                        opacity="0.35"
                                    />
                                    <text
                                        :x="0"
                                        :y="line.y - 1"
                                        class="fill-gray-400 text-[2.8px]"
                                    >
                                        {{ line.value }}
                                    </text>
                                </g>

                                <path
                                    v-if="closedPath"
                                    :d="closedPath"
                                    fill="none"
                                    stroke="#3b82f6"
                                    stroke-width="2.2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    v-if="openPath"
                                    :d="openPath"
                                    fill="none"
                                    stroke="#fb7185"
                                    stroke-width="2.2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                                <g v-for="point in closedPoints" :key="`closed-${point.label}`">
                                    <circle
                                        :cx="point.x"
                                        :cy="point.y"
                                        r="1.5"
                                        fill="#3b82f6"
                                        stroke="white"
                                        stroke-width="0.6"
                                    />
                                </g>
                                <g v-for="point in openPoints" :key="`open-${point.label}`">
                                    <circle
                                        :cx="point.x"
                                        :cy="point.y"
                                        r="1.5"
                                        fill="#fb7185"
                                        stroke="white"
                                        stroke-width="0.6"
                                    />
                                </g>
                            </svg>
                        </div>

                        <div class="mt-4 grid grid-cols-6 gap-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <span v-for="item in chartData" :key="`label-${item.bulan}`">
                                {{ item.bulan }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Info Box: Penjelasan Konsep 5R dan Tiga Metode -->
                <div class="rounded-xl bg-white/95 p-10 shadow-xl shadow-blue-100/40 ring-1 ring-white/60">
                    <div class="mb-6 border-b border-blue-100 pb-4 text-center">
                        <h3 class="text-2xl font-semibold text-gray-900">Apa itu 5R dan bagaimana cara kita mencapainya?</h3>
                        <p class="mt-2 text-sm" style="color: #00529b;">Fondasi budaya kerja bersih, aman, dan selaras pada standar</p>
                    </div>
                    <div class="space-y-6 text-base leading-relaxed text-gray-700 text-center">
                        <p>
                            <span class="font-semibold" style="color: #00529b;">5R</span> adalah budaya kerja yang membangun lingkungan efektif, efisien, dan aman. Lima pilar utamanya dihadirkan sebagai kebiasaan harian yang mudah diingat dan diterapkan.
                        </p>

                        <div class="flex flex-wrap items-center justify-center gap-3 text-sm font-semibold uppercase tracking-wide" style="color: #00529b;">
                            <span class="rounded-full px-4 py-2 shadow-inner" style="background-color: rgba(0, 82, 155, 0.1);">Ringkas</span>
                            <span class="rounded-full px-4 py-2 shadow-inner" style="background-color: rgba(0, 82, 155, 0.1);">Rapi</span>
                            <span class="rounded-full px-4 py-2 shadow-inner" style="background-color: rgba(0, 82, 155, 0.1);">Resik</span>
                            <span class="rounded-full px-4 py-2 shadow-inner" style="background-color: rgba(0, 82, 155, 0.1);">Rawat</span>
                            <span class="rounded-full px-4 py-2 shadow-inner" style="background-color: rgba(0, 82, 155, 0.1);">Rajin</span>
                        </div>

                        <p>
                            Implementasinya dipercepat melalui tiga metode utama: <span class="font-semibold" style="color: #00529b;">GO ACTION</span> (aksi cepat memilah, menata, membuang, dan menertibkan), <span class="font-semibold" style="color: #00529b;">GO BOOST</span> (temuan dan observasi perbaikan), serta <span class="font-semibold" style="color: #00529b;">GO CARE</span> (perbaikan tuntas dan pemeliharaan berkelanjutan).
                        </p>

                        <p class="text-sm text-gray-500">
                            Ketiganya membentuk siklus peningkatan berkelanjutan yang sederhana namun kuat: <span class="font-semibold" style="color: #00529b;">temukan → tindak → rawat</span>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
