<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BackToDashboard from '@/Components/BackToDashboard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    teams: { type: Array, default: () => [] },
    schedules: { type: Array, default: () => [] },
    allUsers: { type: Array, default: () => [] },
    bagianOptions: { type: Array, default: () => [] },
    roleOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const activeTab = ref('tim');
const searchQuery = ref(props.filters.search || '');
const expandedTeamId = ref(props.teams[0]?.id ?? null);

const newTeamForm = useForm({ inspector_area: '' });
const scheduleForm = useForm({
    team_id: null,
    audit_target_id: null,
    scheduled_date: '',
    target_area: '',
    bagian: '',
    notes: '',
});

const addMemberForms = ref({});
const targetForms = ref({});
const teamEditForms = ref({});
const targetEditForms = ref({});
const scheduleEditForms = ref({});

const editingTeamId = ref(null);
const editingTargetKey = ref(null);
const editingScheduleId = ref(null);

const page = usePage();

const initAddMemberForm = (teamId) => {
    if (!addMemberForms.value[teamId]) {
        addMemberForms.value[teamId] = useForm({
            user_id: '',
            is_leader: false,
            set_role_five_r_team: true,
        });
    }
};

const getAddMemberForm = (teamId) => {
    initAddMemberForm(teamId);
    return addMemberForms.value[teamId];
};

const initTargetForm = (team) => {
    if (targetForms.value[team.id]) {
        return;
    }

    const rows = team.audit_targets?.length
        ? team.audit_targets.map((t) => ({
              target_area: t.target_area,
              pic_name: t.pic_name || '',
              pic_user_id: t.pic_user_id || '',
              bagian: t.bagian || '',
          }))
        : [{ target_area: '', pic_name: '', pic_user_id: '', bagian: '' }];
    targetForms.value[team.id] = useForm({ targets: rows });
};

const syncTeamForms = (teams) => {
    teams.forEach((team) => {
        initTargetForm(team);
        initAddMemberForm(team.id);
    });
};

syncTeamForms(props.teams);

watch(
    () => props.teams,
    (teams) => {
        syncTeamForms(teams);

        const flashTeamId = page.props.flash?.new_team_id;
        if (flashTeamId) {
            expandedTeamId.value = Number(flashTeamId);
            return;
        }

        if (!teams.some((t) => t.id === expandedTeamId.value)) {
            expandedTeamId.value = teams[teams.length - 1]?.id ?? null;
        }
    },
    { deep: true, flush: 'sync' },
);

const filteredTeams = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.teams;
    return props.teams.filter((team) => {
        if (team.inspector_area?.toLowerCase().includes(q)) return true;
        if (team.members?.some((m) => m.name?.toLowerCase().includes(q) || m.npp?.includes(q))) return true;
        if (team.audit_targets?.some((t) => t.target_area?.toLowerCase().includes(q) || t.pic_name?.toLowerCase().includes(q))) {
            return true;
        }
        return false;
    });
});

const performSearch = () => {
    router.get(
        route('go_check.management.team'),
        { search: searchQuery.value },
        { preserveState: true, preserveScroll: true },
    );
};

const createTeam = () => {
    newTeamForm.post(route('go_check.management.team.store'), {
        preserveScroll: true,
        onSuccess: () => {
            newTeamForm.reset();
            searchQuery.value = '';
        },
    });
};

const deleteTeam = (teamId, event) => {
    event?.stopPropagation();
    if (!confirm('Hapus tim ini beserta anggota dan penugasannya?')) return;
    router.delete(route('go_check.management.team.destroy', teamId), { preserveScroll: true });
};

const startEditTeam = (team, event) => {
    event?.stopPropagation();
    editingTeamId.value = team.id;
    if (!teamEditForms.value[team.id]) {
        teamEditForms.value[team.id] = useForm({ inspector_area: team.inspector_area });
    } else {
        teamEditForms.value[team.id].inspector_area = team.inspector_area;
    }
};

const cancelEditTeam = (event) => {
    event?.stopPropagation();
    editingTeamId.value = null;
};

const saveTeam = (teamId, event) => {
    event?.stopPropagation();
    teamEditForms.value[teamId]?.put(route('go_check.management.team.update', teamId), {
        preserveScroll: true,
        onSuccess: () => {
            editingTeamId.value = null;
        },
    });
};

const targetEditKey = (teamId, targetId) => `${teamId}-${targetId}`;

const startEditTarget = (teamId, target) => {
    const key = targetEditKey(teamId, target.id);
    editingTargetKey.value = key;
    targetEditForms.value[key] = useForm({
        target_area: target.target_area,
        pic_name: target.pic_name || '',
        bagian: target.bagian || '',
    });
};

const cancelEditTarget = () => {
    editingTargetKey.value = null;
};

const saveTarget = (teamId, targetId) => {
    const key = targetEditKey(teamId, targetId);
    targetEditForms.value[key]?.put(route('go_check.management.team.targets.update', [teamId, targetId]), {
        preserveScroll: true,
        onSuccess: () => {
            editingTargetKey.value = null;
        },
    });
};

const deleteTarget = (teamId, targetId) => {
    if (!confirm('Hapus penugasan area ini?')) return;
    router.delete(route('go_check.management.team.targets.destroy', [teamId, targetId]), { preserveScroll: true });
};

const startEditSchedule = (schedule) => {
    editingScheduleId.value = schedule.id;
    scheduleEditForms.value[schedule.id] = useForm({
        team_id: schedule.team_id,
        scheduled_date: schedule.scheduled_date_raw,
        target_area: schedule.target_area,
        bagian: schedule.bagian || '',
        notes: schedule.notes || '',
    });
};

const cancelEditSchedule = () => {
    editingScheduleId.value = null;
};

const saveScheduleEdit = (scheduleId) => {
    scheduleEditForms.value[scheduleId]?.put(route('go_check.management.schedules.update', scheduleId), {
        preserveScroll: true,
        onSuccess: () => {
            editingScheduleId.value = null;
        },
    });
};

const deleteSchedule = (scheduleId) => {
    if (!confirm('Hapus jadwal Go Check ini?')) return;
    router.delete(route('go_check.management.schedules.destroy', scheduleId), { preserveScroll: true });
};

const toggleTeam = (id) => {
    expandedTeamId.value = expandedTeamId.value === id ? null : id;
};

const addMember = (teamId) => {
    const form = getAddMemberForm(teamId);
    if (!form) return;

    form.post(route('go_check.management.team.members.add', teamId), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const removeMember = (teamId, userId) => {
    if (!confirm('Hapus anggota dari tim ini?')) return;
    router.delete(route('go_check.management.team.members.remove', [teamId, userId]), { preserveScroll: true });
};

const saveTargets = (teamId) => {
    targetForms.value[teamId]?.post(route('go_check.management.team.targets', teamId), { preserveScroll: true });
};

const addTargetRow = (teamId) => {
    targetForms.value[teamId]?.targets.push({ target_area: '', pic_name: '', pic_user_id: '', bagian: '' });
};

const removeTargetRow = (teamId, index) => {
    targetForms.value[teamId]?.targets.splice(index, 1);
};

const selectedTeamForSchedule = computed(() =>
    props.teams.find((t) => t.id === scheduleForm.team_id) ?? null,
);

const onScheduleTeamChange = () => {
    scheduleForm.audit_target_id = null;
    scheduleForm.target_area = '';
    scheduleForm.bagian = '';
};

const onScheduleTargetChange = () => {
    const team = selectedTeamForSchedule.value;
    if (!team || !scheduleForm.audit_target_id) return;
    const target = team.audit_targets?.find((t) => t.id === Number(scheduleForm.audit_target_id));
    if (target) {
        scheduleForm.target_area = target.target_area;
        scheduleForm.bagian = target.bagian || '';
    }
};

const submitSchedule = () => {
    scheduleForm.post(route('go_check.management.schedules.store'), {
        preserveScroll: true,
        onSuccess: () => {
            scheduleForm.reset();
            scheduleForm.team_id = null;
        },
    });
};

const exportExcel = () => {
    window.location.href = route('go_check.management.team.export');
};

const usersNotInTeam = (team) => {
    const memberIds = new Set((team.members || []).map((m) => m.user_id));
    return props.allUsers.filter((u) => !memberIds.has(u.id));
};
</script>

<template>
    <Head title="Tim 5R & Jadwal Go Check" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Kelola Tim 5R & Penugasan Audit</h2>
                    <p class="mt-1 text-sm text-gray-500">Struktur tim per area inspector, penugasan area pengecekan, dan jadwal Go Check.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-xl border border-emerald-600 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100"
                        @click="exportExcel"
                    >
                        Unduh Excel
                    </button>
                    <BackToDashboard :href="route('go_check.management.dashboard')" />
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8 max-w-6xl mx-auto px-4 space-y-6">
            <!-- Tab -->
            <div class="flex gap-2 border-b border-gray-200">
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-semibold border-b-2 -mb-px transition"
                    :class="activeTab === 'tim' ? 'border-[#00529b] text-[#00529b]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    @click="activeTab = 'tim'"
                >
                    Tim & Penugasan
                </button>
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-semibold border-b-2 -mb-px transition"
                    :class="activeTab === 'jadwal' ? 'border-[#00529b] text-[#00529b]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    @click="activeTab = 'jadwal'"
                >
                    Jadwal Go Check
                    <span v-if="schedules.length" class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ schedules.length }}</span>
                </button>
            </div>

            <!-- TAB: Tim -->
            <div v-show="activeTab === 'tim'" class="space-y-6">
                <div class="flex flex-col sm:flex-row gap-3">
                    <form class="flex flex-1 gap-2" @submit.prevent="performSearch">
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Cari nama anggota, area inspector, atau area pengecekan..."
                            class="flex-1 rounded-xl border-gray-200 text-sm shadow-sm focus:ring-[#00529b]"
                        />
                        <PrimaryButton type="submit">Cari</PrimaryButton>
                    </form>
                </div>

                <form class="rounded-2xl bg-white p-4 shadow ring-1 ring-gray-100 flex flex-col sm:flex-row gap-3 items-end" @submit.prevent="createTeam">
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tambah tim area inspector baru</label>
                        <input
                            v-model="newTeamForm.inspector_area"
                            type="text"
                            required
                            placeholder="Contoh: Area Produksi Farma I"
                            class="w-full rounded-lg border-gray-200 text-sm"
                        />
                    </div>
                    <PrimaryButton type="submit" :disabled="newTeamForm.processing">+ Tambah Tim</PrimaryButton>
                </form>

                <p v-if="!filteredTeams.length" class="text-center text-gray-500 py-12 rounded-2xl bg-white ring-1 ring-gray-100">
                    Belum ada tim. Tambahkan tim area inspector sesuai struktur organisasi 5R.
                </p>

                <article
                    v-for="team in filteredTeams"
                    :key="team.id"
                    class="rounded-2xl bg-white shadow ring-1 ring-gray-100 overflow-hidden"
                >
                    <div class="flex items-stretch">
                        <button
                            type="button"
                            class="flex-1 flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50 transition min-w-0"
                            @click="toggleTeam(team.id)"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#00529b]">Area Inspector</p>
                                <div v-if="editingTeamId === team.id && teamEditForms[team.id]" class="mt-2" @click.stop>
                                    <input
                                        v-model="teamEditForms[team.id].inspector_area"
                                        type="text"
                                        required
                                        class="w-full rounded-lg border-gray-200 text-sm font-bold"
                                        @click.stop
                                    />
                                    <div class="mt-2 flex gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg bg-[#00529b] px-3 py-1.5 text-xs font-semibold text-white"
                                            @click="saveTeam(team.id, $event)"
                                        >
                                            Simpan
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg bg-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700"
                                            @click="cancelEditTeam($event)"
                                        >
                                            Batal
                                        </button>
                                    </div>
                                </div>
                                <template v-else>
                                    <h3 class="text-lg font-bold text-gray-900 truncate">{{ team.inspector_area }}</h3>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ team.members?.length || 0 }} anggota · {{ team.audit_targets?.length || 0 }} area pengecekan
                                    </p>
                                </template>
                            </div>
                            <span class="text-gray-400 text-xl shrink-0">{{ expandedTeamId === team.id ? '▾' : '▸' }}</span>
                        </button>
                        <div v-if="editingTeamId !== team.id" class="flex flex-col justify-center gap-1 border-l border-gray-100 px-3 py-2 shrink-0">
                            <button
                                type="button"
                                class="text-xs font-medium text-[#00529b] hover:underline"
                                @click="startEditTeam(team, $event)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="text-xs font-medium text-red-600 hover:underline"
                                @click="deleteTeam(team.id, $event)"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>

                    <div v-show="expandedTeamId === team.id" class="border-t border-gray-100 px-5 py-5 space-y-6 bg-slate-50/50">
                        <!-- Anggota -->
                        <section>
                            <h4 class="font-semibold text-gray-900 mb-3">Anggota Tim 5R</h4>
                            <div v-if="team.members?.length" class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                                        <tr>
                                            <th class="px-4 py-2">No</th>
                                            <th class="px-4 py-2">Nama</th>
                                            <th class="px-4 py-2">NPP</th>
                                            <th class="px-4 py-2">Bagian</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="(m, idx) in team.members" :key="m.user_id">
                                            <td class="px-4 py-2 text-gray-500">{{ idx + 1 }}</td>
                                            <td class="px-4 py-2 font-medium">
                                                {{ m.name }}
                                                <span v-if="m.is_leader" class="ml-1 text-xs text-[#00529b]">(Ketua tim)</span>
                                            </td>
                                            <td class="px-4 py-2 text-gray-600">{{ m.npp }}</td>
                                            <td class="px-4 py-2 text-gray-600">{{ m.bagian || '—' }}</td>
                                            <td class="px-4 py-2 text-right">
                                                <button type="button" class="text-xs text-red-600 hover:underline" @click="removeMember(team.id, m.user_id)">Hapus</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-sm text-gray-500 italic">Belum ada anggota.</p>

                            <form
                                v-if="getAddMemberForm(team.id)"
                                class="mt-4 flex flex-col sm:flex-row gap-2 items-end"
                                @submit.prevent="addMember(team.id)"
                            >
                                <div class="flex-1 w-full">
                                    <label class="text-xs text-gray-600">Tambah anggota dari user terdaftar</label>
                                    <select v-model="getAddMemberForm(team.id).user_id" required class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                        <option value="">— Pilih karyawan —</option>
                                        <option v-for="u in usersNotInTeam(team)" :key="u.id" :value="u.id">
                                            {{ u.name }} (NPP {{ u.npp }}) — {{ u.role }}
                                        </option>
                                    </select>
                                </div>
                                <label class="flex items-center gap-2 text-xs text-gray-600 pb-2">
                                    <input v-model="getAddMemberForm(team.id).is_leader" type="checkbox" />
                                    Ketua tim
                                </label>
                                <PrimaryButton type="submit" class="shrink-0">Tambah</PrimaryButton>
                            </form>
                        </section>

                        <!-- Penugasan area -->
                        <section v-if="targetForms[team.id]">
                            <h4 class="font-semibold text-gray-900 mb-1">Area Pengecekan (yang diaudit tim ini)</h4>
                            <p class="text-xs text-gray-500 mb-3">Sesuai kolom AREA PENGECEKAN & PIC pada laporan Excel tim 5R.</p>

                            <div v-if="team.audit_targets?.length" class="mb-4 overflow-x-auto rounded-xl border border-gray-200 bg-white">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                                        <tr>
                                            <th class="px-4 py-2">Area pengecekan</th>
                                            <th class="px-4 py-2">PIC area</th>
                                            <th class="px-4 py-2">Bagian (Solver)</th>
                                            <th class="px-4 py-2 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="target in team.audit_targets" :key="target.id">
                                            <template v-if="editingTargetKey === targetEditKey(team.id, target.id)">
                                                <td class="px-4 py-2">
                                                    <input
                                                        v-model="targetEditForms[targetEditKey(team.id, target.id)].target_area"
                                                        type="text"
                                                        class="w-full rounded-lg border-gray-200 text-sm"
                                                    />
                                                </td>
                                                <td class="px-4 py-2">
                                                    <input
                                                        v-model="targetEditForms[targetEditKey(team.id, target.id)].pic_name"
                                                        type="text"
                                                        class="w-full rounded-lg border-gray-200 text-sm"
                                                    />
                                                </td>
                                                <td class="px-4 py-2">
                                                    <select
                                                        v-model="targetEditForms[targetEditKey(team.id, target.id)].bagian"
                                                        class="w-full rounded-lg border-gray-200 text-sm"
                                                    >
                                                        <option value="">— Opsional —</option>
                                                        <option v-for="b in bagianOptions" :key="b" :value="b">{{ b }}</option>
                                                    </select>
                                                </td>
                                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                                    <button
                                                        type="button"
                                                        class="text-xs font-medium text-[#00529b] hover:underline mr-3"
                                                        @click="saveTarget(team.id, target.id)"
                                                    >
                                                        Simpan
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="text-xs text-gray-600 hover:underline"
                                                        @click="cancelEditTarget"
                                                    >
                                                        Batal
                                                    </button>
                                                </td>
                                            </template>
                                            <template v-else>
                                                <td class="px-4 py-2 font-medium">{{ target.target_area }}</td>
                                                <td class="px-4 py-2">{{ target.pic_name || target.pic_user_name || '—' }}</td>
                                                <td class="px-4 py-2">{{ target.bagian || '—' }}</td>
                                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                                    <button
                                                        type="button"
                                                        class="text-xs font-medium text-[#00529b] hover:underline mr-3"
                                                        @click="startEditTarget(team.id, target)"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="text-xs text-red-600 hover:underline"
                                                        @click="deleteTarget(team.id, target.id)"
                                                    >
                                                        Hapus
                                                    </button>
                                                </td>
                                            </template>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <p class="text-xs font-medium text-gray-700 mb-2">Tambah penugasan baru</p>
                            <div class="space-y-3">
                                <div
                                    v-for="(row, idx) in targetForms[team.id].targets"
                                    :key="idx"
                                    class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 p-3 rounded-xl bg-white border border-gray-200"
                                >
                                    <div class="sm:col-span-2">
                                        <label class="text-xs text-gray-500">Area pengecekan *</label>
                                        <input v-model="row.target_area" type="text" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="Area Office" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">PIC area</label>
                                        <input v-model="row.pic_name" type="text" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="Nama PIC" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">Bagian (Solver)</label>
                                        <select v-model="row.bagian" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                            <option value="">— Opsional —</option>
                                            <option v-for="b in bagianOptions" :key="b" :value="b">{{ b }}</option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-4 flex justify-end">
                                        <button type="button" class="text-xs text-red-600" @click="removeTargetRow(team.id, idx)">Hapus baris</button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" class="text-sm text-[#00529b] font-medium" @click="addTargetRow(team.id)">+ Tambah area pengecekan</button>
                                <PrimaryButton type="button" @click="saveTargets(team.id)">Simpan penugasan</PrimaryButton>
                            </div>
                        </section>
                    </div>
                </article>
            </div>

            <!-- TAB: Jadwal -->
            <div v-show="activeTab === 'jadwal'" class="space-y-6">
                <form class="rounded-2xl bg-white p-5 shadow ring-1 ring-gray-100 space-y-4" @submit.prevent="submitSchedule">
                    <h3 class="font-semibold text-gray-900">Buat jadwal Go Check</h3>
                    <p class="text-xs text-gray-500">Notifikasi otomatis dikirim ke semua anggota tim saat jadwal dibuat. Pengingat H-0 dikirim jam 07:00 (cron server).</p>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-600">Tim area inspector *</label>
                            <select
                                v-model="scheduleForm.team_id"
                                required
                                class="mt-1 w-full rounded-lg border-gray-200 text-sm"
                                @change="onScheduleTeamChange"
                            >
                                <option :value="null">— Pilih tim —</option>
                                <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.inspector_area }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Tanggal jadwal *</label>
                            <input v-model="scheduleForm.scheduled_date" type="date" required class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                        </div>
                        <div v-if="selectedTeamForSchedule?.audit_targets?.length">
                            <label class="text-xs font-medium text-gray-600">Area pengecekan (dari penugasan)</label>
                            <select
                                v-model="scheduleForm.audit_target_id"
                                class="mt-1 w-full rounded-lg border-gray-200 text-sm"
                                @change="onScheduleTargetChange"
                            >
                                <option :value="null">— Pilih atau isi manual —</option>
                                <option v-for="t in selectedTeamForSchedule.audit_targets" :key="t.id" :value="t.id">
                                    {{ t.target_area }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Area pengecekan *</label>
                            <input v-model="scheduleForm.target_area" type="text" required class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-gray-600">Catatan</label>
                            <textarea v-model="scheduleForm.notes" rows="2" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="Instruksi tambahan untuk tim..." />
                        </div>
                    </div>
                    <PrimaryButton type="submit" :disabled="scheduleForm.processing">Buat jadwal & kirim notifikasi</PrimaryButton>
                </form>

                <section class="rounded-2xl bg-white shadow ring-1 ring-gray-100 overflow-hidden">
                    <h3 class="font-semibold text-gray-900 px-5 py-4 border-b border-gray-100">Jadwal mendatang</h3>
                    <div v-if="!schedules.length" class="p-8 text-center text-sm text-gray-500">Belum ada jadwal aktif.</div>
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="s in schedules" :key="s.id" class="px-5 py-4">
                            <div v-if="editingScheduleId === s.id" class="space-y-3">
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500">Tim</label>
                                        <select
                                            v-model="scheduleEditForms[s.id].team_id"
                                            class="mt-1 w-full rounded-lg border-gray-200 text-sm"
                                        >
                                            <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.inspector_area }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">Tanggal</label>
                                        <input
                                            v-model="scheduleEditForms[s.id].scheduled_date"
                                            type="date"
                                            class="mt-1 w-full rounded-lg border-gray-200 text-sm"
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs text-gray-500">Area pengecekan</label>
                                        <input
                                            v-model="scheduleEditForms[s.id].target_area"
                                            type="text"
                                            class="mt-1 w-full rounded-lg border-gray-200 text-sm"
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs text-gray-500">Catatan</label>
                                        <textarea
                                            v-model="scheduleEditForms[s.id].notes"
                                            rows="2"
                                            class="mt-1 w-full rounded-lg border-gray-200 text-sm"
                                        />
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg bg-[#00529b] px-3 py-1.5 text-xs font-semibold text-white"
                                        @click="saveScheduleEdit(s.id)"
                                    >
                                        Simpan
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg bg-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700"
                                        @click="cancelEditSchedule"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>
                            <div v-else class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ s.scheduled_date }} — {{ s.target_area }}</p>
                                    <p class="text-sm text-gray-600">Tim: {{ s.inspector_area }}</p>
                                    <p v-if="s.notes" class="text-xs text-gray-500 mt-1">{{ s.notes }}</p>
                                </div>
                                <div class="flex flex-wrap gap-3 shrink-0">
                                    <button
                                        type="button"
                                        class="text-sm font-medium text-[#00529b] hover:underline"
                                        @click="startEditSchedule(s)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="text-sm text-red-600 hover:underline"
                                        @click="deleteSchedule(s.id)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
