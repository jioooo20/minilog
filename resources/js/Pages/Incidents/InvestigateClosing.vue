<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AttachmentList from '@/Components/AttachmentList.vue';
import AttachmentUpload from '@/Components/AttachmentUpload.vue';
import ModalConfirm from '@/Components/ModalConfirm.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const props = defineProps({
  mode: {
    type: String,
    default: 'engineer',
  },
  incident: {
    type: Object,
    required: true,
  },
});

const inc = computed(() => props.incident?.data ?? props.incident ?? {});
const isSubmitting = ref(false);
const messageText = ref('');
const messageTone = ref('info');
const closingNotes = ref(inc.value.closing_notes ?? '');
const closingRequested = ref(Boolean(inc.value.closing_requested));
const isSupervisorMode = computed(() => props.mode === 'supervisor');
const confirmOpen = ref(false);
const confirmAction = ref('');

const confirmTitle = computed(() => {
  if (confirmAction.value === 'close') {
    return 'Close Incident?';
  }

  return 'Request Closing to Supervisor?';
});

const confirmMessage = computed(() => {
  if (confirmAction.value === 'close') {
    return 'The incident will be closed and cannot be continued after this action.';
  }

  return 'The closing request will be sent to the supervisor for incident closure processing.';
});

const confirmText = computed(() => {
  if (confirmAction.value === 'close') {
    return 'Yes, Close Incident';
  }

  return 'Yes, Request Closing';
});

const confirmClass = computed(() => {
  if (confirmAction.value === 'close') {
    return 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500';
  }

  return 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500';
});

// Attachment state
const showUploader = ref(false);
const attachmentsList = ref(inc.value.attachments ?? []);

const onAttachmentUploaded = (newAttachments) => {
  attachmentsList.value = newAttachments;
  showUploader.value = false;
};

const onAttachmentDeleted = () => {
  router.reload({ preserveScroll: true });
};

const formatDate = (value) => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
};

const setMessage = (tone, text) => {
  messageTone.value = tone;
  messageText.value = text;
};

const requestClosing = async () => {
  isSubmitting.value = true;
  try {
    await axios.post(route('incidents.request-closing', inc.value.incident_id));
    closingRequested.value = true;
    setMessage('success', 'Request closing already sent.');
    router.visit(route('incidents.show', inc.value.incident_id));
  } catch (error) {
    console.error('requestClosing error', error);
    setMessage('error', error.response?.data?.message || 'Failed to request closing.');
  } finally {
    isSubmitting.value = false;
  }
};

const closeIncident = async () => {
  if (!closingNotes.value.trim()) {
    setMessage('error', 'Closing notes is required.');
    return;
  }

  isSubmitting.value = true;
  try {
    await axios.post(route('incidents.close', inc.value.incident_id), {
      closing_notes: closingNotes.value,
    });

    setMessage('success', 'Incident closed successfully.');
    router.visit(route('incidents.show', inc.value.incident_id));
  } catch (error) {
    console.error('closeIncident error', error);
    setMessage('error', error.response?.data?.message || 'Failed to close incident.');
  } finally {
    isSubmitting.value = false;
  }
};

const openConfirm = (action) => {
  confirmAction.value = action;
  confirmOpen.value = true;
};

const handleConfirm = async () => {
  confirmOpen.value = false;

  if (confirmAction.value === 'close') {
    await closeIncident();
    return;
  }

  await requestClosing();
};
</script>

<template>
  <Head :title="`Request Closing ${inc.incident_code || ''}`" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3">
        <div>
          <p v-if="isSupervisorMode" class="text-xs font-semibold uppercase tracking-wide text-rose-600">Supervisor Closing</p>
          <p v-else class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Engineer Closing Request</p>
          <h1 class="text-lg font-semibold text-slate-900">{{ inc.incident_code || '-' }}</h1>
        </div>
        <Link
          :href="route('incidents.show', inc.incident_id)"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </Link>
      </div>
    </template>

    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
      <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <div class="text-sm font-semibold text-slate-900">{{ inc.title || '-' }}</div>
              <div class="mt-1 text-sm text-slate-500">{{ inc.description || '-' }}</div>
            </div>
            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase text-indigo-700">
              {{ inc.status || '-' }}
            </span>
          </div>

          <dl class="mt-4 grid gap-4 text-sm text-slate-700 sm:grid-cols-2">
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Handled By</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ inc.assigned_to?.name || '-' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Verification At</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ formatDate(inc.verified_at) }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Repair Completed At</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ formatDate(inc.resolved_at) }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Closing Notes</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ inc.closing_notes || '-' }}</dd>
            </div>
          </dl>

          <div v-if="closingRequested" class="mt-4 inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase text-indigo-700">
            Closing sudah diajukan
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h2 v-if="isSupervisorMode" class="text-base font-semibold text-slate-900">Supervisor Closing</h2>
              <h2 v-else class="text-base font-semibold text-slate-900">Request Closing</h2>
              <p v-if="isSupervisorMode" class="text-sm text-slate-500">Read the timeline, write closing notes, then close the incident.</p>
              <p v-else class="text-sm text-slate-500">Submit a request to the supervisor to proceed with the incident closure.</p>
            </div>
            <div v-if="!isSupervisorMode" class="text-xs text-slate-500">No additional notes</div>
          </div>

          <div
            v-if="messageText"
            class="mt-4 rounded-lg border px-4 py-3 text-sm"
            :class="messageTone === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'"
          >
            {{ messageText }}</div>

          <div class="mt-5 space-y-4">
            <div v-if="isSupervisorMode">
              <label class="mb-2 block text-sm font-medium text-slate-700">Closing Notes</label>
              <textarea
                v-model="closingNotes"
                rows="6"
                class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
                placeholder="Write closing notes for the incident."
              />
            </div>

            <div class="flex flex-wrap gap-3">
              <button
                v-if="!isSupervisorMode"
                type="button"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                :disabled="isSubmitting || closingRequested"
                @click="openConfirm('request')"
              >
                Close Request
              </button>
              <button
                v-else
                type="button"
                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                :disabled="isSubmitting"
                @click="openConfirm('close')"
              >
                Close Incident
              </button>
            </div>
          </div>
        </div>
      </section>

      <ModalConfirm
        :show="confirmOpen"
        :title="confirmTitle"
        :message="confirmMessage"
        :confirm-text="confirmText"
        cancel-text="Cancel"
        :processing="isSubmitting"
        :confirm-class="confirmClass"
        @close="confirmOpen = false"
        @confirm="handleConfirm"
      />

      <aside class="space-y-6">
        <!-- Attachment Upload -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Dokumen Pendukung</h2>
            <button
              v-if="!showUploader"
              type="button"
              class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700"
              @click="showUploader = true"
            >
              + Upload
            </button>
          </div>
          <div class="mt-3">
            <div v-if="showUploader" class="mb-3">
              <AttachmentUpload
                :incident-id="inc.incident_id"
                source-group="closing"
                @uploaded="onAttachmentUploaded"
              />
              <button
                type="button"
                class="mt-2 text-xs text-slate-500 hover:text-slate-700"
                @click="showUploader = false"
              >
                Cancel
              </button>
            </div>
            <AttachmentList
              :attachments="attachmentsList"
              :incident-id="inc.incident_id"
              :can-delete="true"
              @deleted="onAttachmentDeleted"
            />
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-base font-semibold text-slate-900">Checklist Closing</h2>
          <ul class="mt-4 space-y-3 text-sm text-slate-700">
            <li class="rounded-lg bg-slate-50 px-3 py-2">Ensure operator verification is complete.</li>
            <li class="rounded-lg bg-slate-50 px-3 py-2">Ensure repair and resolution are recorded.</li>
            <li v-if="isSupervisorMode" class="rounded-lg bg-slate-50 px-3 py-2">Supervisor closes the incident after reviewing the entire timeline.</li>
            <li v-else class="rounded-lg bg-slate-50 px-3 py-2">This request signals the supervisor to close the incident.</li>
          </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-base font-semibold text-slate-900">Audit Trail</h2>
          <div class="mt-4 space-y-3 text-sm text-slate-700">
            <div v-for="log in (inc.audit_logs || []).slice(0, 6)" :key="log.log_id" class="rounded-lg border border-slate-200 p-3">
              <div class="font-semibold text-slate-900">{{ (log.action || '').replace(/_/g, ' ') }}</div>
              <div class="mt-1 text-xs text-slate-500">{{ formatDate(log.created_at) }}</div>
              <div v-if="log.action_details" class="mt-2 text-xs text-slate-600">{{ log.action_details }}</div>
            </div>
            <div v-if="!(inc.audit_logs || []).length" class="text-slate-500">No audit trail available.</div>
          </div>
        </div>
      </aside>
    </div>
  </AuthenticatedLayout>
</template>