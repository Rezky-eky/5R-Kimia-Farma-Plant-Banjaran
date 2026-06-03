<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    images: {
        type: Array,
        required: true,
    },
    initialIndex: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['close']);

const index = ref(props.initialIndex);
const scale = ref(1);
const translate = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0, tx: 0, ty: 0 });

const currentSrc = computed(() => props.images[index.value] ?? '');
const hasMultiple = computed(() => props.images.length > 1);
const scalePercent = computed(() => Math.round(scale.value * 100));

const resetTransform = () => {
    scale.value = 1;
    translate.value = { x: 0, y: 0 };
};

watch(
    () => props.initialIndex,
    (v) => {
        index.value = v;
        resetTransform();
    },
);

watch(index, resetTransform);

const zoomIn = () => {
    scale.value = Math.min(5, +(scale.value + 0.25).toFixed(2));
};

const zoomOut = () => {
    scale.value = Math.max(0.25, +(scale.value - 0.25).toFixed(2));
};

const onWheel = (e) => {
    e.preventDefault();
    const delta = e.deltaY > 0 ? -0.12 : 0.12;
    scale.value = Math.min(5, Math.max(0.25, +(scale.value + delta).toFixed(2)));
};

const onPointerDown = (e) => {
    if (e.button !== 0) return;
    isDragging.value = true;
    dragStart.value = {
        x: e.clientX,
        y: e.clientY,
        tx: translate.value.x,
        ty: translate.value.y,
    };
};

const onPointerMove = (e) => {
    if (!isDragging.value) return;
    translate.value = {
        x: dragStart.value.tx + (e.clientX - dragStart.value.x),
        y: dragStart.value.ty + (e.clientY - dragStart.value.y),
    };
};

const onPointerUp = () => {
    isDragging.value = false;
};

const prev = () => {
    if (index.value > 0) index.value -= 1;
};

const next = () => {
    if (index.value < props.images.length - 1) index.value += 1;
};

const onKeyDown = (e) => {
    if (e.key === 'Escape') emit('close');
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
    if (e.key === '+' || e.key === '=') zoomIn();
    if (e.key === '-') zoomOut();
    if (e.key === '0') resetTransform();
};

onMounted(() => {
    document.body.style.overflow = 'hidden';
    window.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKeyDown);
});
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-[100] flex flex-col bg-black/95"
            role="dialog"
            aria-modal="true"
            aria-label="Pratinjau foto"
            @click.self="emit('close')"
        >
            <!-- Toolbar -->
            <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-white/10 px-3 py-2 sm:px-4">
                <p class="text-sm font-medium text-white/90">
                    Foto {{ index + 1 }} / {{ images.length }}
                    <span class="ml-2 text-white/50">· {{ scalePercent }}%</span>
                </p>
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <button
                        type="button"
                        class="rounded-lg bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20"
                        aria-label="Perkecil"
                        @click="zoomOut"
                    >
                        −
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20"
                        aria-label="Perbesar"
                        @click="zoomIn"
                    >
                        +
                    </button>
                    <button
                        type="button"
                        class="hidden rounded-lg bg-white/10 px-3 py-1.5 text-xs font-medium text-white hover:bg-white/20 sm:inline-block"
                        @click="resetTransform"
                    >
                        Reset
                    </button>
                    <a
                        :href="currentSrc"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-lg bg-white/10 px-3 py-1.5 text-xs font-medium text-white hover:bg-white/20"
                    >
                        Buka tab baru
                    </a>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700"
                        @click="emit('close')"
                    >
                        Tutup
                    </button>
                </div>
            </div>

            <!-- Image area -->
            <div
                class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden touch-none"
                @wheel.prevent="onWheel"
                @pointerdown="onPointerDown"
                @pointermove="onPointerMove"
                @pointerup="onPointerUp"
                @pointerleave="onPointerUp"
                @pointercancel="onPointerUp"
            >
                <button
                    v-if="hasMultiple && index > 0"
                    type="button"
                    class="absolute left-2 z-10 rounded-full bg-black/50 p-3 text-white hover:bg-black/70 sm:left-4"
                    aria-label="Foto sebelumnya"
                    @click.stop="prev"
                >
                    ‹
                </button>

                <img
                    v-if="currentSrc"
                    :src="currentSrc"
                    :alt="`Foto ${index + 1}`"
                    class="max-h-full max-w-full select-none object-contain transition-transform duration-75"
                    :style="{
                        transform: `translate(${translate.x}px, ${translate.y}px) scale(${scale})`,
                        cursor: isDragging ? 'grabbing' : scale > 1 ? 'grab' : 'zoom-in',
                    }"
                    draggable="false"
                    @click.stop="scale < 2 ? zoomIn() : zoomOut()"
                />

                <button
                    v-if="hasMultiple && index < images.length - 1"
                    type="button"
                    class="absolute right-2 z-10 rounded-full bg-black/50 p-3 text-white hover:bg-black/70 sm:right-4"
                    aria-label="Foto berikutnya"
                    @click.stop="next"
                >
                    ›
                </button>
            </div>

            <p class="shrink-0 px-4 py-2 text-center text-xs text-white/50">
                Scroll / pinch untuk zoom · Seret untuk geser · Esc untuk tutup
            </p>
        </div>
    </Teleport>
</template>
