<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() => {
    const base = 'flex items-center justify-start w-full gap-3 px-3 py-2.5 min-h-[42px] text-sm font-medium rounded-lg focus:outline-none transition-all duration-200 ease-out';
    if (props.active) {
        return `${base} text-[#5d7f91] font-semibold shadow-[inset_3px_0_0_#8eafc1,0_4px_12px_rgba(142,175,193,0.12)]`;
    }
    return `${base} text-slate-600 hover:text-[#5d7f91] hover:bg-[#edf4f8] hover:translate-x-1`;
});
</script>

<template>
    <Link
        :href="href"
        :class="classes"
        :style="active ? { backgroundColor: 'rgba(142, 175, 193, 0.16)' } : {}"
    >
        <slot />
    </Link>
</template>
