<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    goCares: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
});

const searchForm = useForm({
    search: props.filters.search || '',
});

const performSearch = () => {
    router.get(route('admin.go_care.index'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchForm.search = '';
    performSearch();
};

const showDetail = ref({});

const toggleDetail = (id) => {
    showDetail.value[id] = !showDetail.value[id];
};
</script>

<template>
    <Head title="Data GO CARE - Admin" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-900 drop-shadow">
                Data GO CARE
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Filter & Search -->
                <div class="mb-6 rounded-2xl bg-white/90 p-6 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60">
                    <form @submit.prevent="performSearch" class="space-y-4 md:space-y-0 md:flex md:items-end md:gap-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                                Pencarian
                            </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Cari berdasarkan nama, NPP, bagian, atau penjelasan..."
                                class="block w-full rounded-xl border-0 bg-white/95 px-4 py-2.5 text-sm text-gray-700 shadow-inner shadow-gray-200/60 transition focus:ring-2 focus:ring-amber-500 focus:ring-offset-0 focus:shadow-lg focus:shadow-amber-100/50"
                            />
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="rounded-xl bg-amber-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-300/50 transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2"
                            >
                                Cari
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="rounded-xl bg-gray-200 px-6 py-2.5 text-sm font-semibold text-gray-700 shadow-lg transition hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                            >
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Data Table -->
                <div class="rounded-2xl bg-white/90 shadow-xl shadow-gray-300/50 ring-1 ring-gray-100/60 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Tanggal
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Karyawan
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Bagian
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Bagian Temuan
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="goCare in goCares.data"
                                    :key="goCare.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {{ goCare.created_at }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div>
                                            <div class="font-medium">{{ goCare.nama_karyawan }}</div>
                                            <div class="text-xs text-gray-500">NPP: {{ goCare.npp_karyawan }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ goCare.bagian }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ goCare.bagian_temuan }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <button
                                            @click="toggleDetail(goCare.id)"
                                            class="text-amber-600 hover:text-amber-900 transition-colors"
                                        >
                                            {{ showDetail[goCare.id] ? 'Sembunyikan' : 'Lihat Detail' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Detail Card (Expandable) -->
                    <div
                        v-for="goCare in goCares.data"
                        :key="`detail-${goCare.id}`"
                        v-show="showDetail[goCare.id]"
                        class="border-t border-gray-200 bg-gray-50 p-6"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <div v-if="goCare.area_temuan">
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Area Temuan</h4>
                                    <p class="text-sm text-gray-700 bg-white p-3 rounded-lg">{{ goCare.area_temuan }}</p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Penjelasan Temuan</h4>
                                    <p class="text-sm text-gray-700 bg-white p-3 rounded-lg">
                                        {{ goCare.penjelasan_temuan }}
                                    </p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Penjelasan CAPA</h4>
                                    <p class="text-sm text-gray-700 bg-white p-3 rounded-lg">
                                        {{ goCare.penjelasan_capa }}
                                    </p>
                                </div>

                                <div v-if="goCare.foto_before && goCare.foto_before.length > 0">
                                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Foto Before ({{ goCare.foto_before.length }})</h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div
                                            v-for="(foto, index) in goCare.foto_before"
                                            :key="index"
                                            class="relative"
                                        >
                                            <img
                                                :src="foto"
                                                :alt="`Foto before ${index + 1}`"
                                                class="w-full h-32 object-cover rounded-lg border border-gray-200"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-4">
                                <div v-if="goCare.foto_after && goCare.foto_after.length > 0">
                                    <h4 class="text-sm font-semibold text-blue-900 mb-2">Foto After ({{ goCare.foto_after.length }})</h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div
                                            v-for="(foto, index) in goCare.foto_after"
                                            :key="index"
                                            class="relative"
                                        >
                                            <img
                                                :src="foto"
                                                :alt="`Foto after ${index + 1}`"
                                                class="w-full h-32 object-cover rounded-lg border border-blue-200"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="goCares.links && goCares.links.length > 3"
                        class="border-t border-gray-200 bg-gray-50 px-6 py-4 flex items-center justify-between"
                    >
                        <div class="text-sm text-gray-700">
                            Menampilkan {{ goCares.from }} sampai {{ goCares.to }} dari {{ goCares.total }} data
                        </div>
                        <div class="flex gap-2">
                            <template v-for="(link, index) in goCares.links" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                        link.active
                                            ? 'bg-amber-600 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200',
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-2 text-sm text-gray-400"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

