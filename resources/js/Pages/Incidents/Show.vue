<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed } from 'vue';

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id ?? null);
const currentUserRole = computed(() => page.props.auth?.user?.role ?? '');

const props = defineProps({
  incident: { type: Object, required: true },
});

// Ensure inc.value is always an object so template property access won't throw
const inc = computed(() => props.incident?.data ?? props.incident ?? {});

// Debugging aid: log incoming prop shape so we can confirm what Inertia sends
/* eslint-disable no-console */
console.debug('Show.vue props.incident', props.incident);
/* eslint-enable no-console */

const formatDate = (value) => {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(d);
};

const formatJson = (value) => {
  if (value === null || value === undefined) return '-';

  if (typeof value === 'string') {
    try {
      return JSON.stringify(JSON.parse(value), null, 2);
    } catch {
      return value;
    }
  }

  return JSON.stringify(value, null, 2);
};

const toFieldRows = (value) => {
  if (value === null || value === undefined) return [];

  let resolved = value;

  if (typeof value === 'string') {
    try {
      resolved = JSON.parse(value);
    } catch {
      return [{ key: 'value', value }];
    }
  }

  if (Array.isArray(resolved)) {
    return resolved.map((entry, index) => ({
      key: `[${index}]`,
      value: typeof entry === 'object' ? formatJson(entry) : String(entry),
    }));
  }

  if (typeof resolved === 'object') {
    return Object.entries(resolved).map(([key, entry]) => ({
      key,
      value: typeof entry === 'object' ? formatJson(entry) : String(entry),
    }));
  }

  return [{ key: 'value', value: String(resolved) }];
};

const canStartInvestigation = computed(() => {
  return currentUserRole.value === 'engineer' && inc.value.status === 'investigating' && inc.value.assigned_to?.id === currentUserId.value;
});

const canOpenRepair = computed(() => {
  return currentUserRole.value === 'engineer' && inc.value.status === 'repairing' && inc.value.assigned_to?.id === currentUserId.value;
});

const canVerifyIncident = computed(() => {
  return currentUserRole.value === 'operator' && inc.value.status === 'verifying' && inc.value.reported_by?.id === currentUserId.value && !inc.value.closing_requested;
});

const canRequestClosing = computed(() => {
  return currentUserRole.value === 'engineer' && inc.value.status === 'verifying' && inc.value.assigned_to?.id === currentUserId.value && !!inc.value.verified_at;
});

const canReviewHypothesis = computed(() => {
  return currentUserRole.value === 'supervisor' && inc.value.status === 'awaiting_approval';
});

const canCloseIncident = computed(() => {
  return currentUserRole.value === 'supervisor' && inc.value.status === 'verifying' && inc.value.closing_requested;
});

</script>

<template>
  <Head :title="`Insiden ${inc.incident_code || ''}`" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3">
            <div>  
                <h1 class="text-lg font-semibold text-slate-900">{{ inc.incident_code || '-' }}</h1>
                <p class="text-sm text-slate-500">{{ inc.title || '-' }}</p>
            </div>
        <div class="text-right">
            
            <div class="flex items-center justify-end gap-2">
                <Link
                  v-if="canStartInvestigation"
                  :href="route('incidents.investigate', inc.incident_id)"
                  class="rounded-lg bg-amber-500 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600"
                >
                  Investigation Form
                </Link>
                <Link
                  v-if="canReviewHypothesis"
                  :href="route('incidents.review', inc.incident_id)"
                  class="rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700"
                >
                  Review Hypothesis
                </Link>
                <Link
                  v-if="canOpenRepair"
                  :href="route('incidents.repair', inc.incident_id)"
                  class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                >
                  Repair Actions
                </Link>
                <Link
                  v-if="canVerifyIncident"
                  :href="route('incidents.verify-form', inc.incident_id)"
                  class="rounded-lg bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-700"
                >
                  Verification Form
                </Link>
                <span
                  v-if="currentUserRole === 'operator' && inc.status === 'verifying' && inc.reported_by?.id === currentUserId && inc.closing_requested"
                  class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700"
                >
                  Closing requested, waiting for verification result
                </span>
                <template v-if="canRequestClosing">
                  <Link
                    v-if="!inc.closing_requested"
                    :href="route('incidents.request-closing-form', inc.incident_id)"
                    class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                  >
                    Request Closing
                  </Link>
                  <span
                    v-else
                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700"
                  >
                    Closing requested, waiting for supervisor review
                  </span>
                </template>
                <template v-if="canCloseIncident">
                  <Link
                    :href="route('incidents.close-form', inc.incident_id)"
                    class="rounded-lg border border-rose-200 bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700"
                  >
                    Close Incident
                  </Link>
                </template>
                <Link
            :href="route('incidents.index')"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              Back
            </Link>
                <div>
                    <div class="text-sm font-semibold text-slate-900">Status: <span class="ml-2 font-normal">{{ inc.status || '-' }}</span></div>
                    <div class="text-xs text-slate-500">Severity: <span class="ml-2 font-normal">{{ inc.severity || '-' }}</span></div>
                </div>
            </div>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div>
            <div class="text-xs text-slate-500">Machine / Asset</div>
            <div class="text-sm font-medium text-slate-900">{{ inc.item?.name || '-' }}</div>
          </div>

          <div>
            <div class="text-xs text-slate-500">Location</div>
            <div class="text-sm font-medium text-slate-900">{{ inc.location?.name || '-' }}</div>
          </div>

          <div>
            <div class="text-xs text-slate-500">Reported by</div>
            <div class="text-sm font-medium text-slate-900">{{ inc.reported_by?.name || '-' }}</div>
          </div>
        </div>

        <div class="mt-4">
          <div class="text-xs text-slate-500">Description</div>
          <div class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ inc.description || '-' }}</div>
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold text-slate-800">Audit Trail</h2>

        <div class="flow-root">
          <ul class="-mb-8">
            <li v-for="log in (inc.audit_logs || [])" :key="log.log_id" class="mb-8">
              <div class="relative pb-8">
                <span class="absolute left-0 top-2 -ml-1 flex h-2 w-2">
                  <span class="absolute inline-flex h-2 w-2 rounded-full bg-slate-400"></span>
                </span>
                <div class="ml-6">
                  <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-slate-900">{{ (log.action || '').replace(/_/g, ' ') }}</div>
                    <div class="text-xs text-slate-500">{{ formatDate(log.created_at) }}</div>
                  </div>

                  <div class="mt-1 text-sm text-slate-700">
                    <div v-if="log.performed_by">by {{ log.performed_by.name }}</div>
                    <div v-if="log.action_details" class="mt-1 text-xs text-slate-600">{{ log.action_details }}</div>
                    <div v-if="log.old_value || log.new_value" class="mt-2 text-xs text-slate-600">
                      <div v-if="log.old_value" class="space-y-1">
                        <div class="font-medium text-slate-700">Previously</div>
                        <div class="max-h-64 overflow-auto rounded-lg border border-slate-200 bg-slate-50">
                          <table class="w-full border-collapse text-left text-[11px] leading-4 text-slate-700">
                            <tbody>
                              <tr v-for="row in toFieldRows(log.old_value)" :key="`old-${row.key}`" class="border-t border-slate-200 first:border-t-0">
                                <th class="w-32 whitespace-nowrap border-r border-slate-200 px-2 py-1.5 align-top font-medium text-slate-600">{{ row.key }}</th>
                                <td class="whitespace-pre-wrap px-2 py-1.5 font-mono break-words">{{ row.value }}</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <div v-if="log.new_value" class="mt-3 space-y-1">
                        <div class="font-medium text-slate-700">Currently</div>
                        <div class="max-h-64 overflow-auto rounded-lg border border-slate-200 bg-slate-50">
                          <table class="w-full border-collapse text-left text-[11px] leading-4 text-slate-700">
                            <tbody>
                              <tr v-for="row in toFieldRows(log.new_value)" :key="`new-${row.key}`" class="border-t border-slate-200 first:border-t-0">
                                <th class="w-32 whitespace-nowrap border-r border-slate-200 px-2 py-1.5 align-top font-medium text-slate-600">{{ row.key }}</th>
                                <td class="whitespace-pre-wrap px-2 py-1.5 font-mono break-words">{{ row.value }}</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>
