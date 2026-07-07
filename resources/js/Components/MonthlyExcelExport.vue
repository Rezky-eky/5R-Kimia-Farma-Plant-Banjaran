<script setup>
import { ref } from 'vue';

const props = defineProps({
    exportRoute: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: 'Unduh Excel',
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const month = ref(new Date().toISOString().slice(0, 7));

const exportExcel = () => {
    const url = route(props.exportRoute, { month: month.value });
    window.location.href = url;
};
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-end"
        :class="compact ? 'sm:items-center' : ''"
    >
        <div :class="compact ? 'w-full sm:w-44' : 'w-full sm:w-56'">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Bulan Laporan
            </label>
            <input
                v-model="month"
                type="month"
                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0"
            />
        </div>
        <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl bg-[#00529b] px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-[#004080] focus:outline-none focus:ring-2 focus:ring-[#00529b] focus:ring-offset-2"
            @click="exportExcel"
        >
            {{ label }}
        </button>
    </div>
</template>
