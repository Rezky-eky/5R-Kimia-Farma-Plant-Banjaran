<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    teamMembers: Array,
    assignments: Array,
    allUsers: Array,
    bagianOptions: Array,
    roleOptions: Array,
});

const selectedUserId = ref(props.teamMembers[0]?.id ?? null);
const selectedBagian = ref([]);

const roleForm = useForm({ user_id: null, role: 'five_r_team' });
const assignForm = useForm({ user_id: null, bagian: [] });

const assignmentFor = (userId) => props.assignments.find((a) => a.user_id === userId)?.bagian ?? [];

const saveRole = () => {
    roleForm.user_id = selectedUserId.value;
    roleForm.post(route('go_check.management.team.role'), { preserveScroll: true });
};

const saveAssignments = () => {
    assignForm.user_id = selectedUserId.value;
    assignForm.bagian = selectedBagian.value;
    assignForm.post(route('go_check.management.team.assignments'), { preserveScroll: true });
};

const loadBagianForUser = (id) => {
    selectedUserId.value = id;
    selectedBagian.value = [...assignmentFor(id)];
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
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">Kelola Tim 5R & Penugasan Bagian</h2>
                <Link :href="route('go_check.management.dashboard')" class="text-sm text-[#00529b]">← Kembali</Link>
            </div>
        </template>

        <div class="py-8 max-w-5xl mx-auto px-4 grid lg:grid-cols-2 gap-8">
            <section class="rounded-2xl bg-white p-6 shadow ring-1 ring-gray-100">
                <h3 class="font-semibold mb-4">Anggota tim</h3>
                <ul class="space-y-2 max-h-80 overflow-y-auto">
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

            <section v-if="selectedUserId" class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow ring-1 ring-gray-100">
                    <h3 class="font-semibold mb-3">Ubah role</h3>
                    <select v-model="roleForm.role" class="w-full rounded-lg border-gray-200 text-sm mb-3">
                        <option v-for="r in roleOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </select>
                    <PrimaryButton type="button" @click="saveRole">Simpan role</PrimaryButton>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow ring-1 ring-gray-100">
                    <h3 class="font-semibold mb-3">Penugasan bagian audit (Finder)</h3>
                    <p class="text-xs text-gray-500 mb-3">Centang bagian yang boleh diaudit oleh anggota ini.</p>
                    <div class="max-h-48 overflow-y-auto space-y-1 mb-4">
                        <label v-for="b in bagianOptions" :key="b" class="flex items-center gap-2 text-sm">
                            <input type="checkbox" :checked="selectedBagian.includes(b)" @change="toggleBagian(b)" />
                            {{ b }}
                        </label>
                    </div>
                    <PrimaryButton type="button" @click="saveAssignments">Simpan penugasan</PrimaryButton>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
