<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';

const loading = ref(false);
const stats = ref({});
const recent = ref([]);
const error = ref(null);

const filters = reactive({
  status: '',
  severity: '',
  search: '',
});

const fetchData = async () => {
  loading.value = true;
  error.value = null;

  try {
    const res = await axios.get(route('dashboard.engineer-data'));
    const payload = res.data;
    stats.value = payload.stats || {};
    recent.value = payload.recent?.data ?? payload.recent ?? [];
  } catch (e) {
    console.error('engineer dashboard fetch', e);
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
    const matchesSeverity = !filters.severity || String(incident.severity || '').toLowerCase() === String(filters.severity).toLowerCase();
    const matchesSearch = !search
      || String(incident.incident_code || '').toLowerCase().includes(search)
      || String(incident.title || '').toLowerCase().includes(search)
      || String(incident.item?.name || '').toLowerCase().includes(search)
      || String(incident.location?.name || '').toLowerCase().includes(search);

    return matchesStatus && matchesSeverity && matchesSearch;
  });
});

const resetFilters = () => {
  filters.status = '';
  filters.severity = '';
  filters.search = '';
};

const statusTone = (status) => {
  const value = String(status || '').toLowerCase();
  if (value === 'investigating') return 'bg-amber-100 text-amber-800 ring-1 ring-amber-200';
  if (value === 'repairing') return 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200';
  if (value === 'verifying') return 'bg-violet-100 text-violet-800 ring-1 ring-violet-200';
  return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
};

const severityTone = (severity) => {
  const value = String(severity || '').toLowerCase();
  if (value === 'critical') return 'bg-rose-100 text-rose-800 ring-1 ring-rose-200';
  if (value === 'high') return 'bg-orange-100 text-orange-800 ring-1 ring-orange-200';
  if (value === 'medium') return 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200';
  return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
};
</script>

<template>

  <Head title="Engineer Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Engineer workspace</p>
          <h1 class="text-xl font-semibold text-slate-900">My Incident Queue</h1>
          <p class="mt-1 text-sm text-slate-500">Work priorities, latest statuses, and quick access to action forms.</p>
        </div>

        <div class="flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-white shadow-sm">
          <div>
            <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Assigned</div>
            <div class="text-2xl font-semibold">{{ stats.total_assigned ?? '-' }}</div>
          </div>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total assigned</div>
          <div class="mt-3 text-3xl font-semibold text-slate-900">{{ stats.total_assigned ?? '-' }}</div>
          <p class="mt-2 text-sm text-slate-500">All incidents under your responsibility.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Open assigned</div>
          <div class="mt-3 text-3xl font-semibold text-slate-900">{{ stats.open_assigned ?? '-' }}</div>
          <p class="mt-2 text-sm text-slate-500">Work items that are not yet closed.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Critical assigned</div>
          <div class="mt-3 text-3xl font-semibold text-rose-700">{{ stats.critical_assigned ?? '-' }}</div>
          <p class="mt-2 text-sm text-slate-500">Requires higher priority.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending repair</div>
          <div class="mt-3 text-3xl font-semibold text-emerald-700">{{ stats.pending_repair ?? '-' }}</div>
          <p class="mt-2 text-sm text-slate-500">Still in the repair phase.</p>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Quick filters</h2>
            <p class="mt-1 text-sm text-slate-500">Filter the work queue without leaving the page.</p>
          </div>

          <div class="flex flex-wrap gap-2">
            <button type="button" @click="resetFilters"
              class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
              Reset
            </button>
            <button type="button" @click="fetchData"
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
              Refresh
            </button>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
            <select v-model="filters.status"
              class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
              <option value="">Any</option>
              <option value="investigating">Investigating</option>
              <option value="repairing">Repairing</option>
              <option value="verifying">Verifying</option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Severity</label>
            <select v-model="filters.severity"
              class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
              <option value="">Any</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
            <input v-model="filters.search" type="text" placeholder="Search code, title, asset, location"
              class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500" />
          </div>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900">My work queue</h2>
            <p class="text-sm text-slate-500">List of incidents assigned to you.</p>
          </div>
          <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700">
            {{ filteredRecent.length }} items
          </div>
        </div>

        <div class="mt-4 space-y-3">
          <div v-if="loading"
            class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
            Loading...
          </div>

          <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            Failed to load data.
          </div>

          <article v-for="inc in filteredRecent" :key="inc.incident_id"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/60 shadow-sm">
            <div class="flex flex-col gap-4 p-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="text-base font-semibold text-slate-900">{{ inc.incident_code || '-' }}</h3>
                  <span :class="['rounded-full px-3 py-1 text-xs font-semibold uppercase', statusTone(inc.status)]">
                    {{ inc.status || '-' }}
                  </span>
                  <span :class="['rounded-full px-3 py-1 text-xs font-semibold uppercase', severityTone(inc.severity)]">
                    {{ inc.severity || '-' }}
                  </span>
                </div>

                <p class="mt-2 text-sm font-medium text-slate-900">{{ inc.title || '-' }}</p>
                <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ inc.description || '-' }}</p>

                <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-slate-600 md:grid-cols-3">
                  <div><span class="text-slate-500">Asset:</span> {{ inc.item?.name || '-' }}</div>
                  <div><span class="text-slate-500">Location:</span> {{ inc.location?.name || '-' }}</div>
                  <div><span class="text-slate-500">Detected:</span> {{ formatDate(inc.detected_at) }}</div>
                </div>
              </div>

              <div class="flex shrink-0 flex-wrap gap-2">
                <a v-if="inc.status === 'investigating'" :href="route('incidents.investigate', inc.incident_id)"
                  class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">
                  Investigate
                </a>
                <a v-else-if="inc.status === 'repairing'" :href="route('incidents.repair', inc.incident_id)"
                  class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                  Repair
                </a>
                <a v-else-if="inc.status === 'verifying'" :href="route('incidents.verify-form', inc.incident_id)"
                  class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700">
                  Verify
                </a>

                <a :href="route('incidents.show', inc.incident_id)"
                  class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                  Detail
                </a>
              </div>
            </div>
          </article>

          <div v-if="!loading && filteredRecent.length === 0"
            class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
            No matching assigned incidents.
          </div>
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>
