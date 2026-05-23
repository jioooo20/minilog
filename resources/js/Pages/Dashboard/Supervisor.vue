<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, computed } from 'vue';
import ClosedCasesChart from '@/Components/Charts/ClosedCasesChart.vue';

const loading = ref(false);
const stats = ref({});
const timeseries = ref([]);
const recent = ref([]);
const error = ref(null);

const fetchDashboard = async () => {
    loading.value = true;
    error.value = null;
    try {
        const res = await axios.get(route('audit.index'), { params: { per_page: 5 } });
        // API returns payload when requested as JSON
            const payload = res.data;
            stats.value = payload.stats || {};
            timeseries.value = payload.timeseries || [];
            // payload.incidents may be in nested format depending on controller
            if (payload.incidents?.data) {
                recent.value = payload.incidents.data;
            } else if (payload.data) {
                recent.value = payload.data;
            } else {
                recent.value = [];
            }
    } catch (e) {
        console.error('fetchDashboard', e);
        error.value = e;
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchDashboard();
});

const formatDate = (value) => {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(d);
};

const avgDays = computed(() => {
    return typeof stats.value.avg_closing_days === 'number' ? Number(stats.value.avg_closing_days).toFixed(1) : stats.value.avg_closing_days ?? '-';
});
</script>

<template>
    <Head title="Supervisor Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">Supervisor Dashboard</h1>
                    <p class="text-sm text-slate-500">Audit summary and final reports for supervisors.</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/audit" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Audit</Link>
                    <Link href="/incidents" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">Incidents</Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-xs text-slate-500">Closed cases</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.total_closed ?? '-' }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-xs text-slate-500">Closed this month</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.this_month ?? '-' }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-xs text-slate-500">Critical closed</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.critical_closed ?? '-' }}</div>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="text-xs text-slate-500">Avg closing days</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ avgDays }}</div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Closed cases (last 6 months)</h3>
                <div class="mt-3">
                    <ClosedCasesChart :timeseries="timeseries" />
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Recent closed reports</h2>
                    <div class="text-xs text-slate-500">Showing latest 5</div>
                </div>

                <div class="mt-4 space-y-3">
                    <div v-if="loading" class="text-sm text-slate-500">Loading...</div>
                    <div v-if="error" class="text-sm text-rose-600">Failed to load dashboard data.</div>

                    <div v-for="inc in recent" :key="inc.incident_id" class="flex items-start justify-between gap-4 rounded-lg border border-slate-100 p-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="text-sm font-semibold text-slate-900">{{ inc.incident_code || '-' }}</div>
                                <div class="text-xs text-slate-500">{{ inc.title || '-' }}</div>
                            </div>
                            <div class="mt-2 text-sm text-slate-600">Asset: {{ inc.item?.name || '-' }} · Location: {{ inc.location?.name || '-' }}</div>
                            <div class="mt-2 text-xs text-slate-500">Closed at: {{ formatDate(inc.closed_at) }} · Closed by: {{ inc.closed_by?.name || '-' }}</div>
                        </div>

                        <div class="flex shrink-0 flex-col items-end gap-2">
                            <a :href="route('audit.final-report', inc.incident_id)" class="rounded-md bg-slate-900 px-3 py-2 text-xs font-medium text-white hover:bg-slate-700">PDF</a>
                            <a :href="route('incidents.show', inc.incident_id)" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">View</a>
                        </div>
                    </div>

                    <div v-if="!loading && recent.length === 0" class="text-sm text-slate-500">No recent closed reports found.</div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
