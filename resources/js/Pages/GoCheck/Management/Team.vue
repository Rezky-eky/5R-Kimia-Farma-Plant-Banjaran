<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';

const props = defineProps({
    teamMembers: { type: Array, default: () => [] },
    assignments: { type: Array, default: () => [] },
    bagianOptions: { type: Array, default: () => [] },
    roleOptions: { type: Array, default: () => [] },
});

const assignmentsList = computed(() => {
    if (Array.isArray(props.assignments)) {
        return props.assignments;
    }
    if (props.assignments && typeof props.assignments === 'object') {
        return Object.values(props.assignments);
    }
    return [];
});

const selectedUserId = ref(props.teamMembers[0]?.id ?? null);
const selectedBagian = ref([]);

const roleForm = useForm({ user_id: null, role: 'five_r_team' });
const assignForm = useForm({ user_id: null, bagian: [] });

const selectedMember = computed(() =>
    props.teamMembers.find((m) => m.id === selectedUserId.value) ?? null,
);

const assignmentFor = (userId) =>
    assignmentsList.value.find((a) => a.user_id === userId)?.bagian ?? [];

const loadBagianForUser = (id) => {
    selectedUserId.value = id;
    selectedBagian.value = [...assignmentFor(id)];
    const member = props.teamMembers.find((m) => m.id === id);
    if (member) {
        roleForm.role = member.role;
    }
};

watch(
    () => props.teamMembers,
    (members) => {
        if (!members?.length) {
            selectedUserId.value = null;
            selectedBagian.value = [];
            return;
        }
        if (!members.some((m) => m.id === selectedUserId.value)) {
            loadBagianForUser(members[0].id);
        }
    },
    { immediate: true },
);

const saveRole = () => {
    roleForm.user_id = selectedUserId.value;
    roleForm.post(route('go_check.management.team.role'), { preserveScroll: true });
};

const saveAssignments = () => {
    assignForm.user_id = selectedUserId.value;
    assignForm.bagian = selectedBagian.value;
    assignForm.post(route('go_check.management.team.assignments'), { preserveScroll: true });
};

const toggleBagian = (bagian) => {
    const i = selectedBagian.value.indexOf(bagian);
    if (i >= 0) selectedBagian.value.splice(i, 1);
    else selectedBagian.value.push(bagian);
};
</script>

<template>
    <Head title="Tim 5R - Go Check" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Kelola Tim 5R & Penugasan Bagian</h2>
                <BackToDashboard :href="route('go_check.management.dashboard')" />
            </div>
        </template>

        <div class="py-6 sm:py-8 max-w-5xl mx-auto grid lg:grid-cols-2 gap-6 lg:gap-8">
            <section class="rounded-2xl bg-white p-4 sm:p-6 shadow ring-1 ring-gray-100">
                <h3 class="font-semibold mb-4">Anggota tim</h3>
                <p v-if="!teamMembers.length" class="text-sm text-gray-500 rounded-lg bg-amber-50 border border-amber-100 p-4">
                    Belum ada anggota dengan role Tim 5R / Ketua / Sekretaris. Ubah role user di bawah setelah menambahkan dari daftar pengguna (via database atau panel admin).
                </p>
                <ul v-else class="space-y-2 max-h-80 overflow-y-auto overscroll-contain">
                    <li
                        v-for="m in teamMembers"
                        :key="m.id"
                        class="p-3 rounded-lg cursor-pointer transition"
                        :class="selectedUserId === m.id ? 'bg-[#00529b]/10 ring-1 ring-[#00529b]/30' : 'hover:bg-gray-50'"
                        @click="loadBagianForUser(m.id)"
                    >
                        <span class="font-medium">{{ m.name }}</span>
                        <span class="text-xs text-gray-500 block">NPP {{ m.npp }} · {{ m.role }}</span>
                    </li>
                </ul>
            </section>

            <section v-if="selectedUserId && selectedMember" class="space-y-6">
                <p class="text-sm text-gray-600 lg:hidden">
                    Mengelola: <strong>{{ selectedMember.name }}</strong>
                </p>

                <div class="rounded-2xl bg-white p-4 sm:p-6 shadow ring-1 ring-gray-100">
                    <h3 class="font-semibold mb-3">Ubah role — {{ selectedMember.name }}</h3>
                    <select v-model="roleForm.role" class="w-full rounded-lg border-gray-200 text-sm mb-3">
                        <option v-for="r in roleOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </select>
                    <PrimaryButton type="button" @click="saveRole">Simpan role</PrimaryButton>
                </div>

                <div class="rounded-2xl bg-white p-4 sm:p-6 shadow ring-1 ring-gray-100">
                    <h3 class="font-semibold mb-3">Penugasan bagian audit (Finder)</h3>
                    <p class="text-xs text-gray-500 mb-3">Centang bagian yang boleh diaudit oleh anggota ini.</p>
                    <div v-if="!bagianOptions.length" class="text-sm text-red-600">
                        Daftar bagian tidak tersedia. Muat ulang halaman atau hubungi admin.
                    </div>
                    <div v-else class="max-h-48 overflow-y-auto overscroll-contain space-y-1 mb-4">
                        <label v-for="b in bagianOptions" :key="b" class="flex items-center gap-2 text-sm py-1">
                            <input type="checkbox" :checked="selectedBagian.includes(b)" @change="toggleBagian(b)" />
                            {{ b }}
                        </label>
                    </div>
                    <PrimaryButton type="button" @click="saveAssignments">Simpan penugasan</PrimaryButton>
                </div>
            </section>

            <section
                v-else-if="teamMembers.length"
                class="rounded-2xl bg-white p-6 shadow ring-1 ring-gray-100 text-sm text-gray-500"
            >
                Pilih anggota tim di panel kiri untuk mengubah role atau penugasan bagian.
            </section>
        </div>
    </AuthenticatedLayout>
</template>
