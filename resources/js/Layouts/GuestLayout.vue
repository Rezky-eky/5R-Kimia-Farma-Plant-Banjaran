<script setup>
</script>

<template>
    <div
        class="flex min-h-screen max-h-screen overflow-hidden flex-col items-center justify-center bg-gradient-to-br from-slate-50 via-white to-slate-100 py-4 px-4"
        style="background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f1f5f9 100%);"
    >
        <div
            class="w-full max-h-[calc(100vh-2rem)] overflow-y-auto bg-white px-6 py-5 shadow-2xl rounded-2xl ring-1 ring-gray-200/60 backdrop-blur-sm sm:max-w-md"
            style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);"
        >
            <slot />
        </div>
    </div>
</template>
