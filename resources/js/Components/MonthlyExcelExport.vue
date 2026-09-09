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
    allowRange: {
        type: Boolean,
        default: true,
    },
});

const mode = ref('single');
const month = ref(new Date().toISOString().slice(0, 7));
const endMonth = ref(month.value);

const exportExcel = () => {
    const params = mode.value === 'range'
        ? { month: month.value, end_month: endMonth.value }
        : { month: month.value };
    const url = route(props.exportRoute, params);
    window.location.href = url;
};
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end"
        :class="compact ? 'sm:items-center' : ''"
    >
        <div :class="compact ? 'w-full sm:w-44' : 'w-full sm:w-56'">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Bulan mulai</label>
            <input
                v-model="month"
                type="month"
                class="block w-full rounded-lg border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-[#86a7a0] focus:ring-2 focus:ring-[#86a7a0]/30"
            />
        </div>
        <template v-if="allowRange">
            <div class="flex min-h-[42px] items-center gap-4 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-600">
                <label class="inline-flex items-center gap-2">
                    <input v-model="mode" type="radio" value="single" class="text-[#6f9690]" />
                    1 bulan
                </label>
                <label class="inline-flex items-center gap-2">
                    <input v-model="mode" type="radio" value="range" class="text-[#6f9690]" />
                    Rentang
                </label>
            </div>
            <div v-if="mode === 'range'" :class="compact ? 'w-full sm:w-44' : 'w-full sm:w-56'">
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Bulan akhir</label>
                <input
                    v-model="endMonth"
                    type="month"
                    class="block w-full rounded-lg border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-[#86a7a0] focus:ring-2 focus:ring-[#86a7a0]/30"
                />
            </div>
        </template>
        <button
            type="button"
            class="inline-flex min-h-[42px] items-center justify-center rounded-lg bg-[#789e98] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#668c86] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#86a7a0]/40 focus:ring-offset-2"
            @click="exportExcel"
        >
            {{ label }}
        </button>
    </div>
</template>
