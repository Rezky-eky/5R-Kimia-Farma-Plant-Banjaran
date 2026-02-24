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

const classes = computed(() =>
    props.active
        ? 'block w-full rounded-lg px-4 py-3 text-base font-medium focus:outline-none transition-all duration-200 ease-in-out'
        : 'block w-full rounded-lg px-4 py-3 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 focus:outline-none transition-all duration-200 ease-in-out',
);
</script>

<template>
    <Link 
        :href="href" 
        :class="classes"
        :style="active ? { color: '#00529b', backgroundColor: 'rgba(0, 82, 155, 0.1)', fontWeight: '600' } : {}"
    >
        <slot />
    </Link>
</template>
