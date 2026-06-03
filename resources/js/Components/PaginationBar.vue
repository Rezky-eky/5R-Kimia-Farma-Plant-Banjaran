<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    /** Objek paginator Laravel (from, to, total, links, current_page, last_page) */
    paginator: {
        type: Object,
        required: true,
    },
    /** Label satuan data, mis. "data", "hasil", "transaksi" */
    itemLabel: {
        type: String,
        default: 'data',
    },
});

const hasPages = computed(() => {
    const p = props.paginator;
    if (!p?.links?.length) return false;
    return p.links.length > 3 || (p.last_page && p.last_page > 1);
});

const summaryText = computed(() => {
    const p = props.paginator;
    const from = p.from ?? 0;
    const to = p.to ?? 0;
    const total = p.total ?? 0;
    let text = `Menampilkan ${from} sampai ${to} dari ${total} ${props.itemLabel}`;
    if (p.current_page && p.last_page && p.last_page > 1) {
        text += ` · Hal ${p.current_page} dari ${p.last_page}`;
    }
    return text;
});
</script>

<template>
    <div
        v-if="hasPages"
        class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
    >
        <p class="text-sm text-gray-700">
            {{ summaryText }}
        </p>
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            <template v-for="(link, index) in paginator.links" :key="`${link.label}-${index}`">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    :class="[
                        'min-w-[2.25rem] rounded-lg px-3 py-2 text-center text-sm font-medium transition',
                        link.active
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50',
                    ]"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="min-w-[2.25rem] cursor-not-allowed rounded-lg bg-gray-100 px-3 py-2 text-center text-sm font-medium text-gray-400"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
