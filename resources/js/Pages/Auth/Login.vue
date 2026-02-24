<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    npp: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Login - 5R Kimia Farma Plant Banjaran" />

        <!-- Header Section (logo + judul + tagline + judul form) - ringkas agar fit viewport -->
        <div class="mb-4 text-center">
            <div class="flex justify-center mb-3">
                <ApplicationLogo class="h-14 w-auto" />
            </div>
            <h1 class="text-xl md:text-2xl font-bold mb-1.5" style="color: #00529b;">
                5R Kimia Farma Plant Banjaran
            </h1>
            <div class="inline-flex items-center px-3 py-1.5 rounded-full bg-slate-50 border border-gray-200 mt-2">
                <p class="text-xs font-medium italic" style="color: #00529b;">Berdaya</p>
                <span class="mx-1.5 text-gray-400">•</span>
                <p class="text-xs text-gray-600">Bersih dalam bekerja, amanah dalam berkarya</p>
            </div>
            <div class="border-t border-gray-200 my-3 max-w-xs mx-auto"></div>
            <h2 class="text-lg font-bold text-gray-800 mb-1">Masuk ke Akun</h2>
            <p class="text-xs text-gray-600">Silakan masukkan NPP dan password Anda</p>
        </div>

        <!-- Status Message -->
        <div v-if="status" class="mb-4 rounded-xl bg-blue-50 border border-blue-200 p-3 text-sm font-medium text-blue-800">
            {{ status }}
        </div>

        <!-- Login Form -->
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="npp" value="Nomor Pokok Pegawai (NPP)" class="mb-2" />

                <TextInput
                    id="npp"
                    type="text"
                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    v-model="form.npp"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Masukkan NPP Anda"
                />

                <InputError class="mt-2" :message="form.errors.npp" />
            </div>

            <div>
                <InputLabel for="password" value="Password" class="mb-2" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan password Anda"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Ingat Saya</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                    style="color: #00529b;"
                >
                    Lupa password?
                </Link>
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-3 text-base font-semibold rounded-xl shadow-lg transition-all duration-200 hover:shadow-xl"
                    style="background-color: #00529b;"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="!form.processing">Masuk</span>
                    <span v-else class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses...
                    </span>
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
