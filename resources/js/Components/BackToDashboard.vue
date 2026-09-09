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
        class="inline-flex min-h-[42px] shrink-0 items-center justify-center rounded-lg border border-[#dfe7e2] bg-[#fffdfa] px-3.5 py-2 text-sm font-semibold text-slate-600 shadow-sm transition-colors hover:border-[#c8dbd4] hover:bg-[#f2f7f4] focus:outline-none focus:ring-2 focus:ring-[#9bb5a5]/40 focus:ring-offset-2"
    >
        Kembali ke Dashboard
    </Link>
</template>
