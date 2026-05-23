<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import ClosedCasesChart from '@/Components/Charts/ClosedCasesChart.vue';

const loading = ref(false);
const error = ref(null);
const stats = ref({});
const recent = ref([]);
const timeseries = ref([]);

const filters = reactive({
    status: '',
    onlyVerificationNeeded: false,
    search: '',
});

const fetchData = async () => {
    loading.value = true;
    error.value = null;

    try {
        const res = await axios.get(route('dashboard.operator-data'));
        const payload = res.data;
        stats.value = payload.stats || {};
        timeseries.value = payload.timeseries || [];
        recent.value = payload.recent?.data ?? payload.recent ?? [];
    } catch (e) {
        console.error('operator dashboard fetch', e);
        error.value = e;
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);

const formatDate = (value) => {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(d);
};

const filteredRecent = computed(() => {
    const search = filters.search.trim().toLowerCase();

    return (recent.value || []).filter((incident) => {
        const matchesStatus = !filters.status || String(incident.status) === String(filters.status);
        const matchesVerification = !filters.onlyVerificationNeeded || !!incident.closing_requested;
        const matchesSearch = !search
            || String(incident.incident_code || '').toLowerCase().includes(search)
            || String(incident.title || '').toLowerCase().includes(search)
            || String(incident.item?.name || '').toLowerCase().includes(search)
            || String(incident.location?.name || '').toLowerCase().includes(search);

        return matchesStatus && matchesVerification && matchesSearch;
    });
});

const resetFilters = () => {
    filters.status = '';
    filters.onlyVerificationNeeded = false;
    filters.search = '';
};

const statusTone = (status) => {
    const value = String(status || '').toLowerCase();
    if (value === 'open') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
    if (value === 'investigating') return 'bg-amber-100 text-amber-800 ring-1 ring-amber-200';
    if (value === 'repairing') return 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200';
    if (value === 'verifying') return 'bg-violet-100 text-violet-800 ring-1 ring-violet-200';
    if (value === 'closed') return 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200';
    return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
};
</script>

<template>
    <Head title="Operator Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Operator workspace</p>
                    <h1 class="text-xl font-semibold text-slate-900">My Reports & Verification Tasks</h1>
                    <p class="mt-1 text-sm text-slate-500">Focus on incident reporting, verification, and monitoring your report statuses.</p>
                </div>

            </div>
        </template>

        <div class="space-y-6">
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total reported</div>
                    <div class="mt-3 text-3xl font-semibold text-slate-900">{{ stats.total_reported ?? '-' }}</div>
                    <p class="mt-2 text-sm text-slate-500">All incidents you have reported.</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Open reported</div>
                    <div class="mt-3 text-3xl font-semibold text-slate-900">{{ stats.open_reported ?? '-' }}</div>
                    <p class="mt-2 text-sm text-slate-500">Still active and not yet closed.</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Closed reported</div>
                    <div class="mt-3 text-3xl font-semibold text-emerald-700">{{ stats.closed_reported ?? '-' }}</div>
                    <p class="mt-2 text-sm text-slate-500">Incidents that have been resolved and closed.</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending verification</div>
                    <div class="mt-3 text-3xl font-semibold text-violet-700">{{ stats.pending_verification ?? '-' }}</div>
                    <p class="mt-2 text-sm text-slate-500">Incidents that are pending verification by an engineer.</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Awaiting closure request</div>
                    <div class="mt-3 text-3xl font-semibold text-amber-700">{{ stats.awaiting_closure_request ?? '-' }}</div>
                    <p class="mt-2 text-sm text-slate-500">Incidents that are awaiting a closure request from an engineer.</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Quick filters</h2>
                        <p class="text-sm text-slate-500">Search your reports by status or keyword.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="resetFilters" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Reset
                        </button>
                        <button type="button" @click="fetchData" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                            Refresh
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select v-model="filters.status" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">Any</option>
                            <option value="open">Open</option>
                            <option value="investigating">Investigating</option>
                            <option value="awaiting_approval">Awaiting Approval</option>
                            <option value="repairing">Repairing</option>
                            <option value="verifying">Verifying</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Focus</label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700">
                            <input v-model="filters.onlyVerificationNeeded" type="checkbox" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500" />
                            Only items ready for my verification
                        </label>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input v-model="filters.search" type="text" placeholder="Search code, title, asset, location" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500" />
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Report trend</h2>
                        <p class="text-sm text-slate-500">Number of reports you submitted in the last 6 months.</p>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <ClosedCasesChart
                        :timeseries="timeseries"
                        title="Reports submitted over the last 6 months"
                        series-label="Reports submitted"
                    />
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">My recent reports</h2>
                        <p class="text-sm text-slate-500">List of reports you created and their latest status.</p>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700">
                        {{ filteredRecent.length }} items
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    <div v-if="loading" class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        Loading...
                    </div>

                    <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        Failed to load data.
                    </div>

                    <article v-for="incident in filteredRecent" :key="incident.incident_id" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/60 shadow-sm">
                        <div class="flex flex-col gap-4 p-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-900">{{ incident.incident_code || '-' }}</h3>
                                    <span :class="['rounded-full px-3 py-1 text-xs font-semibold uppercase', statusTone(incident.status)]">
                                        {{ incident.status || '-' }}
                                    </span>
                                    <span v-if="incident.closing_requested" class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold uppercase text-violet-800 ring-1 ring-violet-200">
                                        Verification requested
                                    </span>
                                </div>

                                <p class="mt-2 text-sm font-medium text-slate-900">{{ incident.title || '-' }}</p>
                                <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ incident.description || '-' }}</p>

                                <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-slate-600 md:grid-cols-3">
                                    <div><span class="text-slate-500">Asset:</span> {{ incident.item?.name || '-' }}</div>
                                    <div><span class="text-slate-500">Location:</span> {{ incident.location?.name || '-' }}</div>
                                    <div><span class="text-slate-500">Detected:</span> {{ formatDate(incident.detected_at) }}</div>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-2">
                                <a
                                    v-if="incident.status === 'verifying' && incident.closing_requested"
                                    :href="route('incidents.verify-form', incident.incident_id)"
                                    class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700"
                                >
                                    Verify Now
                                </a>

                                <a
                                    :href="route('incidents.show', incident.incident_id)"
                                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    Detail
                                </a>
                            </div>
                        </div>
                    </article>

                    <div v-if="!loading && filteredRecent.length === 0" class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                        No matching reported incidents.
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
