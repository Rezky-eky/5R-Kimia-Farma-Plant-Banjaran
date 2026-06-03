<script setup>
import { ref, computed } from 'vue';
import PhotoLightbox from '@/Components/PhotoLightbox.vue';

const props = defineProps({
    images: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: '',
    },
    /** grid-cols-2, dll. */
    gridClass: {
        type: String,
        default: 'grid-cols-2 sm:grid-cols-3',
    },
    /** Kelas tinggi thumbnail, mis. h-32 */
    thumbnailHeightClass: {
        type: String,
        default: 'h-32',
    },
    /** Mode thumbnail kecil (satu foto di tabel) */
    compact: {
        type: Boolean,
        default: false,
    },
});

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

const normalizedImages = computed(() =>
    (props.images || []).filter((src) => typeof src === 'string' && src.length > 0),
);

const openAt = (index) => {
    lightboxIndex.value = index;
    lightboxOpen.value = true;
};

const closeLightbox = () => {
    lightboxOpen.value = false;
};
</script>

<template>
    <div v-if="normalizedImages.length > 0">
        <h4 v-if="title && !compact" class="mb-2 text-sm font-semibold text-gray-900">
            {{ title }}
            <span v-if="normalizedImages.length > 1" class="font-normal text-gray-500">
                ({{ normalizedImages.length }})
            </span>
        </h4>

        <!-- Thumbnail kecil di tabel -->
        <button
            v-if="compact"
            type="button"
            class="group relative overflow-hidden rounded-lg border border-gray-200 bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#00529b]"
            :aria-label="title || 'Lihat foto'"
            @click="openAt(0)"
        >
            <img
                :src="normalizedImages[0]"
                :alt="title || 'Foto'"
                class="h-10 w-10 object-cover transition group-hover:opacity-80 sm:h-12 sm:w-12"
            />
            <span
                class="absolute inset-0 flex items-center justify-center bg-black/0 text-[10px] font-medium text-white opacity-0 transition group-hover:bg-black/40 group-hover:opacity-100"
            >
                Zoom
            </span>
        </button>

        <!-- Grid thumbnail -->
        <div v-else class="grid gap-2" :class="gridClass">
            <button
                v-for="(src, index) in normalizedImages"
                :key="`${src}-${index}`"
                type="button"
                class="group relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50 text-left focus:outline-none focus:ring-2 focus:ring-[#00529b] focus:ring-offset-1"
                @click="openAt(index)"
            >
                <img
                    :src="src"
                    :alt="`${title || 'Foto'} ${index + 1}`"
                    class="w-full object-cover transition group-hover:opacity-90"
                    :class="thumbnailHeightClass"
                />
                <span
                    class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent px-2 py-1.5 text-xs font-medium text-white opacity-0 transition group-hover:opacity-100"
                >
                    Klik untuk perbesar
                </span>
            </button>
        </div>

        <PhotoLightbox
            v-if="lightboxOpen"
            :images="normalizedImages"
            :initial-index="lightboxIndex"
            @close="closeLightbox"
        />
    </div>
</template>
