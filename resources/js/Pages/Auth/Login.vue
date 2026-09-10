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
            <h1 class="mb-1.5 text-xl font-bold text-[#5d7f91] md:text-2xl">
                5R Kimia Farma Plant Banjaran
            </h1>
            <div class="mt-2 inline-flex items-center rounded-full border border-[#c9dce6] bg-[#edf4f8] px-3 py-1.5">
                <p class="text-xs font-medium italic text-[#66889b]">Berdaya</p>
                <span class="mx-1.5 text-[#9ebfd1]">•</span>
                <p class="text-xs text-slate-600">Bersih dalam bekerja, amanah dalam berkarya</p>
            </div>
            <div class="border-t border-gray-200 my-3 max-w-xs mx-auto"></div>
            <h2 class="text-lg font-bold text-gray-800 mb-1">Masuk ke Akun</h2>
            <p class="text-xs text-gray-600">Silakan masukkan NPP dan password Anda</p>
        </div>

        <!-- Status Message -->
        <div v-if="status" class="mb-4 rounded-xl border border-[#c9dce6] bg-[#edf4f8] p-3 text-sm font-medium text-[#5d7f91]">
            {{ status }}
        </div>

        <!-- Login Form -->
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="npp" value="Nomor Pokok Pegawai (NPP)" class="mb-2" />

                <TextInput
                    id="npp"
                    type="text"
                    class="mt-1 block w-full rounded-lg border-[#d8e2dd] shadow-sm focus:border-[#86a7a0] focus:ring-[#86a7a0]/30"
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
                    class="mt-1 block w-full rounded-lg border-[#d8e2dd] shadow-sm focus:border-[#86a7a0] focus:ring-[#86a7a0]/30"
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
                    class="text-sm text-[#648b84] transition-colors hover:text-[#4f726c]"
                >
                    Lupa password?
                </Link>
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="motion-lift w-full justify-center rounded-xl bg-[#8eafc1] py-3 text-base font-semibold text-white shadow-md"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="!form.processing">Login</span>
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
