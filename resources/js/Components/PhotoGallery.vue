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

const isImage = (url) => {
    if (!url) return false;
    return url.match(/\.(jpeg|jpg|gif|png|webp|svg|heic|heif)(\?.*)?$/i) != null;
};

const getFileName = (url) => {
    if (!url) return 'File';
    return url.split('/').pop().split('?')[0];
};

const getFileExt = (url) => {
    if (!url) return '';
    return url.split('.').pop().toLowerCase().split('?')[0];
};

const openAt = (index) => {
    const src = normalizedImages.value[index];
    if (isImage(src)) {
        lightboxIndex.value = index;
        lightboxOpen.value = true;
    } else {
        window.open(src, '_blank');
    }
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
            :aria-label="title || 'Lihat lampiran'"
            @click="openAt(0)"
        >
            <template v-if="isImage(normalizedImages[0])">
                <img
                    :src="normalizedImages[0]"
                    :alt="title || 'Foto'"
                    class="h-10 w-10 object-cover transition group-hover:opacity-80 sm:h-12 sm:w-12"
                />
            </template>
            <template v-else>
                <div class="h-10 w-10 sm:h-12 sm:w-12 flex items-center justify-center bg-gray-200 text-gray-500 font-bold uppercase text-xs">
                    {{ getFileExt(normalizedImages[0]) }}
                </div>
            </template>
            <span
                class="absolute inset-0 flex items-center justify-center bg-black/0 text-[10px] font-medium text-white opacity-0 transition group-hover:bg-black/40 group-hover:opacity-100"
            >
                Buka
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
                <template v-if="isImage(src)">
                    <img
                        :src="src"
                        :alt="`${title || 'Foto'} ${index + 1}`"
                        class="w-full object-cover transition group-hover:opacity-90"
                        :class="thumbnailHeightClass"
                    />
                </template>
                <template v-else>
                    <div class="w-full bg-gray-200 flex flex-col items-center justify-center text-gray-600 transition group-hover:opacity-90 p-4" :class="thumbnailHeightClass">
                        <svg class="w-8 h-8 mb-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span class="text-xs font-medium uppercase break-all text-center line-clamp-2" :title="getFileName(src)">{{ getFileName(src) }}</span>
                    </div>
                </template>
                <span
                    class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent px-2 py-1.5 text-xs font-medium text-white opacity-0 transition group-hover:opacity-100"
                >
                    Klik untuk perbesar/buka
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
