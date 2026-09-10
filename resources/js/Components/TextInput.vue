<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl border border-[#d5e2e8] bg-white/90 px-3 py-2 text-sm text-gray-700 shadow-sm transition focus:border-[#8eafc1] focus:ring-2 focus:ring-[#8eafc1]/30"
        v-model="model"
        ref="input"
    />
</template>
