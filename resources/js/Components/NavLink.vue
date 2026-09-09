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
        return `${base} text-[#5f827f] font-semibold shadow-[inset_3px_0_0_#86a7a0,0_4px_12px_rgba(126,159,154,0.10)]`;
    }
    return `${base} text-slate-600 hover:text-[#506f6d] hover:bg-[#eaf1ee] hover:translate-x-1`;
});
</script>

<template>
    <Link
        :href="href"
        :class="classes"
        :style="active ? { backgroundColor: 'rgba(126, 159, 154, 0.14)' } : {}"
    >
        <slot />
    </Link>
</template>
