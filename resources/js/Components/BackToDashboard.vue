<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    /** URL tujuan eksplisit (opsional) */
    href: {
        type: String,
        default: null,
    },
    /** Paksa ke admin dashboard */
    admin: {
        type: Boolean,
        default: null,
    },
});

const page = usePage();

const targetHref = computed(() => {
    if (props.href) {
        return props.href;
    }

    if (props.admin === true) {
        return route('admin.dashboard');
    }

    if (page.props.auth?.user?.can_manage_go_check && route().current('go_check.management.*')) {
        return route('go_check.management.dashboard');
    }

    if (
        page.props.auth?.user?.role === 'admin' ||
        route().current('admin.*')
    ) {
        return route('admin.dashboard');
    }

    return route('dashboard');
});
</script>

<template>
    <Link
        :href="targetHref"
        class="inline-flex shrink-0 items-center justify-center rounded-xl bg-gray-800 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-colors hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2"
    >
        Kembali ke Dashboard
    </Link>
</template>
