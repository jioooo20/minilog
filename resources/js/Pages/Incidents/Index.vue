<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ModalConfirm from '@/Components/ModalConfirm.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { reactive, computed, ref } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.role ?? '');
const UserId = computed(() => page.props.auth?.user?.id ?? null);
const isTakingOver = ref(false);
const takingOverIncidentId = ref(null);
const confirmTakeOverOpen = ref(false);

const assignIncident = async (incidentId) => {
  takingOverIncidentId.value = incidentId;
  confirmTakeOverOpen.value = true;
};

const performTakeOver = async () => {
  if (!takingOverIncidentId.value) return;

  isTakingOver.value = true;
  try {
    await axios.post(route('incidents.assign', takingOverIncidentId.value));
    // refresh current index to update statuses without showing raw JSON
    confirmTakeOverOpen.value = false;
    router.visit(route('incidents.investigate', takingOverIncidentId.value));
  } catch (e) {
    // handle error (optional: show toast)
    console.error('assignIncident error', e);
  } finally {
    isTakingOver.value = false;
    takingOverIncidentId.value = null;
  }
};

const requestClosing = async (incidentId) => {
  try {
    await axios.post(route('incidents.request-closing', incidentId));
    router.reload({ preserveScroll: true });
  } catch (e) {
    console.error('requestClosing error', e);
  }
};

const props = defineProps({
  incidents: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  items: {
    type: Array,
    default: () => [],
  },
});

const form = reactive({
  status: props.filters.status ?? '',
  severity: props.filters.severity ?? '',
  item_id: props.filters.item_id ?? '',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
});

const applyFilters = () => {
  router.get(
    route('incidents.index'),
    {
      status: form.status || undefined,
      severity: form.severity || undefined,
      item_id: form.item_id || undefined,
      date_from: form.date_from || undefined,
      date_to: form.date_to || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

const resetFilters = () => {
  form.status = '';
  form.severity = '';
  form.item_id = '';
  form.date_from = '';
  form.date_to = '';
  applyFilters();
};

const formatDate = (value) => {
  if (!value) {
    return '-';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date);
};

const severityClass = (sev) => {
  const s = String(sev ?? '').toLowerCase();
  if (s === 'critical') return 'rounded-full bg-rose-300 px-2 py-1 text-xs font-semibold uppercase text-rose-800';
  if (s === 'high') return 'rounded-full bg-amber-300 px-2 py-1 text-xs font-semibold uppercase text-amber-800';
  if (s === 'medium') return 'rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold uppercase text-yellow-800';
  return 'rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold uppercase text-slate-700';
};

const rowSeverityClass = (sev) => {
  const s = String(sev ?? '').toLowerCase();
  if (s === 'critical') return 'bg-rose-100';
  if (s === 'high') return 'bg-orange-100';
  if (s === 'medium') return 'bg-yellow-50';
  return '';
};

// per-row investigation CTA uses inline condition in template
</script>

<template>
  <Head title="Incidents" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3">
        <div>
          <h1 class="text-lg font-semibold text-slate-900">Incident List</h1>
        </div>
        <Link v-if="userRole === 'operator'"
          :href="route('incidents.create')"
          class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600"
        >
          Create
        </Link>
      </div>
    </template>

    <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-5">
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
          <select
            v-model="form.status"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          >
            <option value="">All status</option>
            <option value="open">Open</option>
            <option value="investigating">Investigating</option>
            <option value="awaiting_approval">Awaiting Approval</option>
            <option value="repairing">Repairing</option>
            <option value="verifying">Verifying</option>
            <option value="closed">Closed</option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Severity</label>
          <select
            v-model="form.severity"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          >
            <option value="">All severity</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Machine/Asset</label>
          <select
            v-model="form.item_id"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          >
            <option value="">All assets</option>
            <option v-for="item in items" :key="item.item_id" :value="String(item.item_id)">
              {{ item.item_name }}
            </option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date from</label>
          <input
            v-model="form.date_from"
            type="date"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          />
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date to</label>
          <input
            v-model="form.date_to"
            type="date"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          />
        </div>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
          @click="applyFilters"
        >
            Apply Filters
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="resetFilters"
        >
          Reset
        </button>
      </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
              <th class="px-4 py-3">Code</th>
              <th class="px-4 py-3">Machine/Asset</th>
              <th class="px-4 py-3">Location</th>
              <th class="px-4 py-3">Severity</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Detected</th>
              <th class="px-4 py-3 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
            <tr v-if="!incidents.data?.length">
              <td colspan="7" class="px-4 py-8 text-center text-slate-500">No incident records yet.</td>
            </tr>
            <tr v-for="incident in incidents.data" :key="incident.incident_id" :class="rowSeverityClass(incident.severity)">
              <td class="px-4 py-3 font-medium text-slate-900">{{ incident.incident_code || '-' }}</td>
              <td class="px-4 py-3">{{ incident.item?.name || '-' }}</td>
                <td class="px-4 py-3">{{ incident.location?.name || '-' }}</td>
              <td class="px-4 py-3">
                <span :class="severityClass(incident.severity)">{{ incident.severity || '-' }}</span>
              </td>
              <td class="px-4 py-3">
                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold uppercase text-slate-700">
                  {{ incident.status || '-' }}
                </span>
              </td>
              <td class="px-4 py-3">{{ formatDate(incident.detected_at) }}</td>
              <td class="px-4 py-3 text-center">
                <div class="flex items-center ">
                  <a
                  :href="route('incidents.show', incident.incident_id)"
                  class="ml-3 inline-flex items-center rounded-md border border-slate-900 px-3 text-sm font-semibold text-slate-900 hover:text-slate-600"
                >
                  Detail
                </a>
                <Link
                  v-if="userRole === 'engineer' && incident.status === 'investigating' && incident.assigned_to?.id === UserId"
                  :href="route('incidents.investigate', incident.incident_id)"
                  class="ml-3 inline-flex items-center rounded-md bg-emerald-800 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-600"
                >
                  Investigation Form
                </Link>
                
                <Link
                 v-if="userRole === 'supervisor' && incident.status === 'awaiting_approval'"
                  :href="route('incidents.review', incident.incident_id)"
                  class="ml-3 inline-flex items-center rounded-md bg-sky-600 px-3 py-1 text-xs font-semibold text-white hover:bg-sky-700"
                >
                  Review Hypothesis
                </Link>
                <Link
                  v-if="userRole === 'engineer' && incident.status === 'repairing' && incident.assigned_to?.id === UserId"
                  :href="route('incidents.repair', incident.incident_id)"
                  class="ml-3 inline-flex items-center rounded-md bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
                >
                  Repair Actions
                </Link>
                <Link
                  v-if="userRole === 'operator' && incident.status === 'verifying' && incident.reported_by?.id === UserId && !incident.closing_requested"
                  :href="route('incidents.verify-form', incident.incident_id)"
                  class="ml-3 inline-flex items-center rounded-md bg-violet-600 px-3 py-1 text-xs font-semibold text-white hover:bg-violet-700"
                >
                  Verification Form
                </Link>
                <span
                  v-if="userRole === 'operator' && incident.status === 'verifying' && incident.reported_by?.id === UserId && incident.closing_requested"
                  class="ml-3 inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700"
                >
                  Verification Form Locked (Closing Requested)
                </span>
                <template v-if="userRole === 'engineer' && incident.status === 'verifying' && incident.assigned_to?.id === UserId">
                  <Link
                    v-if="!incident.closing_requested && incident.verified_at"
                    :href="route('incidents.request-closing-form', incident.incident_id)"
                    class="ml-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700"
                  >
                    Request Closing
                  </Link>
                  <span
                    v-if="userRole ==='engineer' && incident.status === 'verifying' && incident.assigned_to?.id === UserId && incident.closing_requested"
                    class="ml-3 inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700"
                  >
                    Closing already requested
                  </span>
                </template>
                <template v-if="userRole === 'supervisor' && incident.status === 'verifying' && incident.closing_requested">
                  <Link
                    :href="route('incidents.close-form', incident.incident_id)"
                    class="ml-3 inline-flex items-center rounded-md bg-rose-600 px-3 py-1 text-xs font-semibold text-white hover:bg-rose-700"
                  >
                    Close Incident
                  </Link>
                </template>
                <template v-if="userRole === 'engineer' && incident.status === 'open'">
                  <button
                    type="button"
                    :disabled="isTakingOver"
                    @click="assignIncident(incident.incident_id)"
                    class="ml-3 inline-flex items-center rounded-md bg-slate-900 px-3 py-1 text-xs font-semibold text-white hover:bg-slate-600"
                  >
                    Take Over
                  </button>
                </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
        <div>
          Show {{ incidents.meta?.from ?? 0 }} - {{ incidents.meta?.to ?? 0 }} of
          {{ incidents.meta?.total ?? 0 }} data
        </div>
        <div class="flex items-center gap-2">
          <template v-for="link in incidents.meta?.links || []" :key="link.label">
            <button
              v-if="link.url"
              type="button"
              class="rounded-md border px-3 py-1 text-sm"
              :class="link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
              @click="router.visit(link.url, { preserveScroll: true, preserveState: true })"
              v-html="link.label"
            />
            <span
              v-else
              class="rounded-md border border-slate-200 bg-slate-100 px-3 py-1 text-sm text-slate-400"
              v-html="link.label"
            />
          </template>
        </div>
      </div>
    </section>

    <ModalConfirm
      :show="confirmTakeOverOpen"
      title="Confirm Take Over?"
      message="Are you sure you want to take over this incident? This action will assign the incident to you and you will be responsible for handling it."
      confirm-text="Yes, take over"
      cancel-text="No"
      :processing="isTakingOver"
      confirm-class="bg-slate-900 hover:bg-slate-700 focus:ring-slate-500"
      @close="confirmTakeOverOpen = false"
      @confirm="performTakeOver"
    />
  </AuthenticatedLayout>
</template>
