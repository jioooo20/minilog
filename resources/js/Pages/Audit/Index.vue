<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
  incidents: {
    type: Object,
    required: true,
  },
  items: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
});

const form = reactive({
  severity: props.filters.severity ?? '',
  item_id: props.filters.item_id ?? '',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  search: props.filters.search ?? '',
});

const applyFilters = () => {
  router.get(
    route('audit.index'),
    {
      severity: form.severity || undefined,
      item_id: form.item_id || undefined,
      date_from: form.date_from || undefined,
      date_to: form.date_to || undefined,
      search: form.search || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

const resetFilters = () => {
  form.severity = '';
  form.item_id = '';
  form.date_from = '';
  form.date_to = '';
  form.search = '';
  applyFilters();
};

const formatDate = (value) => {
  if (!value) return '-';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(parsed);
};

const severityBadgeClass = (severity) => {
  const value = String(severity ?? '').toLowerCase();
  if (value === 'critical') return 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
  if (value === 'high') return 'bg-orange-100 text-orange-700 ring-1 ring-orange-200';
  if (value === 'medium') return 'bg-amber-100 text-amber-700 ring-1 ring-amber-200';
  return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
};

const statusBadgeClass = computed(() => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200');

const summaryCards = computed(() => [
  { label: 'Closed Cases', value: props.stats.total_closed ?? 0, tone: 'bg-slate-900 text-white' },
  { label: 'This Month', value: props.stats.this_month ?? 0, tone: 'bg-sky-600 text-white' },
  { label: 'Critical Closed', value: props.stats.critical_closed ?? 0, tone: 'bg-rose-600 text-white' },
  { label: 'Avg. Closing Days', value: props.stats.avg_closing_days ? Number(props.stats.avg_closing_days).toFixed(1) : '0.0', tone: 'bg-emerald-600 text-white' },
]);

const incidentsData = computed(() => props.incidents?.data ?? []);
</script>

<template>
  <Head title="Audit Final Reports" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Audit</p>
          <h1 class="text-xl font-semibold text-slate-900">Final Reports</h1>
          <p class="mt-1 text-sm text-slate-500">List of closed incidents, formatted as report cards for final review and PDF download.</p>
        </div>
        <div class="rounded-2xl bg-slate-900 px-4 py-3 text-center text-white shadow-sm">
          <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Closed cases</div>
          <div class="text-2xl font-semibold">{{ stats.total_closed ?? 0 }}</div>
        </div>
      </div>
    </template>

    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in summaryCards" :key="card.label" :class="['rounded-2xl px-4 py-4 shadow-sm', card.tone]">
          <div class="text-xs uppercase tracking-[0.2em] opacity-80">{{ card.label }}</div>
          <div class="mt-2 text-3xl font-semibold">{{ card.value }}</div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
        <div class="lg:col-span-2">
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
          <input
            v-model="form.search"
            type="text"
            placeholder="Search code, title, or closing notes"
            class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          />
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Severity</label>
          <select v-model="form.severity" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="">All severity</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Machine/Asset</label>
          <select v-model="form.item_id" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="">All assets</option>
            <option v-for="item in items" :key="item.item_id" :value="String(item.item_id)">{{ item.item_name }}</option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date from</label>
          <input v-model="form.date_from" type="date" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500" />
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date to</label>
          <input v-model="form.date_to" type="date" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500" />
        </div>
      </div>

      <div class="flex flex-wrap gap-2">
        <button type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" @click="applyFilters">
          Apply Filters
        </button>
        <button type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="resetFilters">
          Reset
        </button>
        <div class="flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
          Closed only
        </div>
      </div>
    </section>

    <section class="mt-6">
      <div v-if="!incidentsData.length" class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">No closed reports yet</h2>
        <p class="mt-2 text-sm text-slate-500">Tidak ada insiden closed yang cocok dengan filter saat ini.</p>
      </div>

      <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
        <article
          v-for="incident in incidentsData"
          :key="incident.incident_id"
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div class="border-b border-slate-100 bg-gradient-to-r from-slate-900 to-slate-700 px-5 py-4 text-white">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Incident Code</div>
                <h2 class="mt-1 text-lg font-semibold">{{ incident.incident_code || '-' }}</h2>
              </div>
              <span :class="['rounded-full px-3 py-1 text-xs font-semibold uppercase', statusBadgeClass]">Closed</span>
            </div>
          </div>

          <div class="space-y-4 p-5">
            <div>
              <h3 class="text-base font-semibold text-slate-900">{{ incident.title || '-' }}</h3>
              <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ incident.description || '-' }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Asset</div>
                <div class="mt-1 font-medium text-slate-900">{{ incident.item?.name || '-' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Location</div>
                <div class="mt-1 font-medium text-slate-900">{{ incident.location?.name || '-' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Closed At</div>
                <div class="mt-1 font-medium text-slate-900">{{ formatDate(incident.closed_at) }}</div>
              </div>
              <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Closed By</div>
                <div class="mt-1 font-medium text-slate-900">{{ incident.closed_by?.name || '-' }}</div>
              </div>
            </div>

            <div class="flex flex-wrap gap-2">
              <span :class="['rounded-full px-3 py-1 text-xs font-semibold uppercase', severityBadgeClass(incident.severity)]">{{ incident.severity || '-' }}</span>
              <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700 ring-1 ring-slate-200">{{ incident.status || '-' }}</span>
              <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">{{ incident.audit_logs_count ?? 0 }} audit logs</span>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
              <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Final Notes</div>
              <p class="mt-2 line-clamp-4 whitespace-pre-line">{{ incident.closing_notes || incident.verification_notes || incident.corrective_actions || '-' }}</p>
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
              <Link
                :href="route('audit.final-report', incident.incident_id)"
                class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
              >
                Download Final PDF
              </Link>
              <Link
                :href="route('incidents.show', incident.incident_id)"
                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              >
                View Incident
              </Link>
            </div>
          </div>
        </article>
      </div>

      <div v-if="incidents?.links?.length" class="mt-6 flex flex-wrap items-center justify-center gap-2">
        <Link
          v-for="link in incidents.links"
          :key="link.label"
          :href="link.url || ''"
          :class="[
            'rounded-xl border px-3 py-2 text-sm font-medium',
            link.active
              ? 'border-slate-900 bg-slate-900 text-white'
              : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
            !link.url && 'pointer-events-none opacity-40'
          ]"
          v-html="link.label"
        />
      </div>
    </section>
  </AuthenticatedLayout>
</template>
