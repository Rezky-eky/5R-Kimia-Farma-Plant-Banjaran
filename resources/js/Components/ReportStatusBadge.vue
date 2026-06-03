<script setup>
import { computed } from 'vue';

const props = defineProps({
    /** go_action | go_boost | go_care | ... */
    type: {
        type: String,
        default: '',
    },
    status: {
        type: String,
        default: '',
    },
});

/** Go Action tidak punya alur approve/reject — tampilkan strip saja */
const displayStatus = computed(() => {
    if (props.type === 'go_action') {
        if (!props.status || props.status === '-' || props.status === 'Pending') {
            return '-';
        }
        return props.status;
    }
    return props.status || '-';
});

const showBadge = computed(() => displayStatus.value && displayStatus.value !== '-');

const badgeClass = computed(() => {
    const s = (displayStatus.value || '').toUpperCase();
    if (s === 'APPROVED' || s === 'AUDITED') {
        return 'bg-green-100 text-green-800';
    }
    if (s === 'REJECTED') {
        return 'bg-red-100 text-red-800';
    }
    if (s === 'PENDING' || s === 'OPEN') {
        return 'bg-amber-100 text-amber-800';
    }
    if (s === 'CLOSED' || s === 'SELESAI') {
        return 'bg-blue-100 text-blue-800';
    }
    return 'bg-gray-100 text-gray-700';
});
</script>

<template>
    <span v-if="!showBadge" class="text-sm text-gray-400">—</span>
    <span
        v-else
        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
        :class="badgeClass"
    >
        {{ displayStatus }}
    </span>
</template>
