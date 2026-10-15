<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: 'Tanggal laporan *',
    },
    modelValue: {
        type: String,
        required: true,
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);
const today = usePage().props.today;
</script>

<template>
    <div>
        <InputLabel :for="id" :value="label" />
        <input
            :id="id"
            type="date"
            :max="today"
            :value="modelValue"
            required
            class="mt-2 block w-full rounded-xl border-0 bg-white/95 px-3 py-2 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0"
            @input="emit('update:modelValue', $event.target.value)"
        />
        <p class="mt-1 text-xs text-gray-500">Default hari ini; pilih tanggal lampau jika diperlukan.</p>
        <InputError class="mt-2" :message="error" />
    </div>
</template>
