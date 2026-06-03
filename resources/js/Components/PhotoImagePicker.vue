<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    maxFiles: { type: Number, default: 5 },
    maxFileSize: { type: Number, default: 10 * 1024 * 1024 },
    label: { type: String, default: 'Foto' },
    hint: { type: String, default: 'Maksimal 5 foto, masing-masing maks. 10MB (JPG, PNG, GIF).' },
    disabled: { type: Boolean, default: false },
    inputId: { type: String, default: () => `photo-picker-${Math.random().toString(36).slice(2, 9)}` },
});

const emit = defineEmits(['update:modelValue']);

const photoPreviews = ref([]);
const localError = ref('');
const cameraInputRef = ref(null);
const galleryInputRef = ref(null);

const count = computed(() => props.modelValue?.length ?? 0);
const atLimit = computed(() => count.value >= props.maxFiles);

const syncPreviewsFromModel = () => {
    if (!props.modelValue?.length) {
        photoPreviews.value = [];
    }
};

watch(() => props.modelValue?.length, syncPreviewsFromModel);

const processFiles = (event) => {
    localError.value = '';
    const files = Array.from(event.target.files || []);
    const totalCount = count.value + files.length;

    if (totalCount > props.maxFiles) {
        localError.value = `Maksimal ${props.maxFiles} foto. Anda memilih ${totalCount} foto.`;
        event.target.value = '';
        return;
    }

    const validFiles = [];
    for (const file of files) {
        if (file.size > props.maxFileSize) {
            localError.value = `File "${file.name}" melebihi 10MB dan diabaikan.`;
            continue;
        }
        validFiles.push(file);
    }

    if (!validFiles.length) {
        event.target.value = '';
        return;
    }

    emit('update:modelValue', [...(props.modelValue || []), ...validFiles]);

    validFiles.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreviews.value.push({
                id: `${Date.now()}-${Math.random()}`,
                url: e.target.result,
                file,
            });
        };
        reader.readAsDataURL(file);
    });

    event.target.value = '';
};

const removePhoto = (previewId) => {
    const index = photoPreviews.value.findIndex((p) => p.id === previewId);
    if (index === -1) return;
    const preview = photoPreviews.value[index];
    emit(
        'update:modelValue',
        (props.modelValue || []).filter((f) => f !== preview.file),
    );
    photoPreviews.value.splice(index, 1);
};

const openCamera = () => {
    if (!props.disabled && !atLimit.value) {
        cameraInputRef.value?.click();
    }
};

const openGallery = () => {
    if (!props.disabled && !atLimit.value) {
        galleryInputRef.value?.click();
    }
};
</script>

<template>
    <div class="photo-image-picker">
        <p v-if="label" class="text-sm font-medium text-gray-700">{{ label }}</p>
        <p v-if="hint" class="mt-1 text-xs text-gray-500">{{ hint }}</p>

        <input
            :id="`${inputId}-camera`"
            ref="cameraInputRef"
            type="file"
            accept="image/*"
            capture="environment"
            multiple
            class="hidden"
            :disabled="disabled || atLimit"
            @change="processFiles"
        />
        <input
            :id="`${inputId}-gallery`"
            ref="galleryInputRef"
            type="file"
            accept="image/*"
            multiple
            class="hidden"
            :disabled="disabled || atLimit"
            @change="processFiles"
        />

        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-[#00529b]/40 bg-blue-50 px-4 py-3 text-sm font-semibold text-[#00529b] transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="disabled || atLimit"
                @click="openCamera"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Ambil foto
            </button>
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="disabled || atLimit"
                @click="openGallery"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Pilih dari galeri
            </button>
        </div>

        <p v-if="count > 0" class="mt-2 text-xs text-gray-600">
            Foto terpilih: <strong>{{ count }}/{{ maxFiles }}</strong>
        </p>
        <div v-if="localError" class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
            {{ localError }}
        </div>

        <div v-if="photoPreviews.length > 0" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div
                v-for="(preview, index) in photoPreviews"
                :key="preview.id"
                class="relative rounded-xl border border-gray-200 bg-gray-50 p-2"
            >
                <button
                    type="button"
                    class="absolute -right-2 -top-2 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs text-white hover:bg-red-600"
                    aria-label="Hapus foto"
                    @click="removePhoto(preview.id)"
                >
                    ×
                </button>
                <img
                    :src="preview.url"
                    :alt="`${label} ${index + 1}`"
                    class="h-28 w-full rounded-lg object-cover sm:h-32"
                />
                <p class="mt-1 text-center text-xs text-gray-500">Foto {{ index + 1 }}</p>
            </div>
        </div>

        <slot />
    </div>
</template>
