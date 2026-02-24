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
    const base = 'flex items-center justify-start w-full gap-3 px-4 py-3 text-sm font-medium rounded-xl focus:outline-none transition-all duration-200 ease-out';
    if (props.active) {
        return `${base} text-[#00529b] font-semibold`;
    }
    return `${base} text-slate-600 hover:text-slate-900 hover:bg-slate-100/90`;
});
</script>

<template>
    <Link
        :href="href"
        :class="classes"
        :style="active ? { backgroundColor: 'rgba(0, 82, 155, 0.08)' } : {}"
    >
        <slot />
    </Link>
</template>
