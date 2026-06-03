<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    transactions: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ user: '', type: '' }),
    },
});

const filterForm = useForm({
    user: props.filters.user || '',
    type: props.filters.type || '',
});

const applyFilter = () => {
    router.get(route('admin.points.index'), filterForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilter = () => {
    filterForm.user = '';
    filterForm.type = '';
    applyFilter();
};
</script>

<template>
    <Head title="Riwayat Poin - Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                    Riwayat Poin Pengguna
                </h2>
                <BackToDashboard admin />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Filter -->
                <div class="rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <form
                        @submit.prevent="applyFilter"
                        class="grid grid-cols-1 gap-4 items-end md:grid-cols-3"
                    >
                        <div class="col-span-1 md:col-span-2">
                            <label for="user" class="block text-sm font-medium text-gray-700 mb-2">
                                Cari User (Nama / NPP)
                            </label>
                            <input
                                id="user"
                                v-model="filterForm.user"
                                type="text"
                                placeholder="Contoh: Randi / 2004..."
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                            />
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                Jenis Aktivitas
                            </label>
                            <select
                                id="type"
                                v-model="filterForm.type"
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-[#00529b] focus:ring-offset-0 focus:shadow-[0_0_0_3px_rgba(0,82,155,0.2)]"
                            >
                                <option value="">Semua</option>
                                <option
                                    v-for="t in types"
                                    :key="t"
                                    :value="t"
                                >
                                    {{ t }}
                                </option>
                            </select>
                        </div>

                        <div class="flex gap-2 justify-end">
                            <button
                                type="button"
                                @click="clearFilter"
                                class="rounded-xl bg-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                            >
                                Reset
                            </button>
                            <button
                                type="submit"
                                class="rounded-xl bg-slate-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-400/50 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                            >
                                Terapkan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="rounded-2xl bg-white/90 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Waktu
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        User
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Jenis
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Deskripsi
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Poin
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="tx in transactions.data"
                                    :key="tx.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {{ tx.created_at }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-medium">
                                            {{ tx.user_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            NPP: {{ tx.user_npp || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-800">
                                            {{ tx.type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ tx.description || '-' }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold"
                                        :class="tx.points >= 0 ? 'text-blue-600' : 'text-red-600'"
                                    >
                                        {{ tx.points > 0 ? '+' + tx.points : tx.points }}
                                    </td>
                                </tr>

                                <tr v-if="!transactions.data.length">
                                    <td colspan="5" class="px-6 py-6 text-center text-sm text-gray-500">
                                        Belum ada transaksi poin.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <PaginationBar :paginator="transactions" item-label="transaksi" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

{
  "cells": [],
  "metadata": {
    "language_info": {
      "name": "python"
    }
  },
  "nbformat": 4,
  "nbformat_minor": 2
}