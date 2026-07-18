<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AttachmentList from '@/Components/AttachmentList.vue';
import AttachmentUpload from '@/Components/AttachmentUpload.vue';
import ModalConfirm from '@/Components/ModalConfirm.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
  incident: {
    type: Object,
    required: true,
  },
});

const inc = computed(() => props.incident?.data ?? props.incident ?? {});
const isSubmitting = ref(false);
const statusMessage = ref('');
const statusTone = ref('info');
const confirmOpen = ref(false);
const confirmAction = ref('');

const confirmTitle = computed(() => {
  if (confirmAction.value === 'proposal') {
    return 'Confirm Proposal Submission';
  }

  return 'Confirm Draft Save';
});

const confirmMessage = computed(() => {
  if (confirmAction.value === 'proposal') {
    return 'Are you sure you want to submit this proposal for review?';
  }

  return 'Are you sure you want to save this draft?';
});

const confirmText = computed(() => {
  if (confirmAction.value === 'proposal') {
    return 'Yes, submit proposal';
  }

  return 'Yes, save draft';
});

const confirmClass = computed(() => {
  if (confirmAction.value === 'proposal') {
    return 'bg-amber-500 hover:bg-amber-600 focus:ring-amber-500';
  }

  return 'bg-slate-900 hover:bg-slate-700 focus:ring-slate-500';
});

const form = reactive({
  investigation_notes: inc.value.investigation_notes ?? '',
  root_cause_hypothesis: inc.value.root_cause_hypothesis ?? '',
});

// Attachment state
const showUploader = ref(false);
const attachmentsList = ref(inc.value.attachments ?? []);

// Group attachments by source
const createAttachments = computed(() =>
  attachmentsList.value.filter((a) => a.source === 'create')
);
const investigationAttachments = computed(() =>
  attachmentsList.value.filter((a) => a.source === 'investigation')
);
const otherAttachments = computed(() =>
  attachmentsList.value.filter((a) => a.source !== 'create' && a.source !== 'investigation')
);
const repairAttachments = computed(() =>
  otherAttachments.value.filter((a) => a.source === 'repair')
);
const verificationAttachments = computed(() =>
  otherAttachments.value.filter((a) => a.source === 'verification')
);
const closingAttachments = computed(() =>
  otherAttachments.value.filter((a) => a.source === 'closing')
);

const onAttachmentUploaded = (newAttachments) => {
  attachmentsList.value = newAttachments;
  showUploader.value = false;
};

const onAttachmentDeleted = () => {
  // After delete, we reload page to get updated list
  router.reload({ preserveScroll: true });
};

const formatDate = (value) => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
};

const setMessage = (tone, message) => {
  statusTone.value = tone;
  statusMessage.value = message;
};

const saveDraft = async () => {
  isSubmitting.value = true;
  try {
    const response = await axios.patch(route('incidents.investigate-draft', inc.value.incident_id), {
      investigation_notes: form.investigation_notes,
      root_cause_hypothesis: form.root_cause_hypothesis || null,
    });

    form.investigation_notes = response.data?.investigation_notes ?? form.investigation_notes;
    form.root_cause_hypothesis = response.data?.root_cause_hypothesis ?? form.root_cause_hypothesis;
    setMessage('success', 'Investigation draft saved.');
  } catch (error) {
    console.error('saveDraft error', error);
    setMessage('error', error.response?.data?.message || 'Failed to save investigation draft.');
  } finally {
    isSubmitting.value = false;
  }
};

const submitProposal = async () => {
  if (!form.root_cause_hypothesis.trim()) {
    setMessage('error', 'Root cause hypothesis is required before submitting the proposal.');
    return;
  }

  isSubmitting.value = true;
  try {
    await axios.post(route('incidents.propose', inc.value.incident_id), {
      investigation_notes: form.investigation_notes,
      root_cause_hypothesis: form.root_cause_hypothesis,
    });

    router.visit(route('incidents.show', inc.value.incident_id));
  } catch (error) {
    console.error('submitProposal error', error);
    setMessage('error', error.response?.data?.message || 'Failed to submit proposal.');
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

  if (confirmAction.value === 'proposal') {
    await submitProposal();
    return;
  }

  await saveDraft();
};
</script>

<template>
  <Head :title="`Investigation ${inc.incident_code || ''}`" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Engineer Investigation</p>
          <h1 class="text-lg font-semibold text-slate-900">{{ inc.incident_code || '-' }}</h1>
          <p class="text-sm text-slate-500">Review and update the investigation details below.</p>
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
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-sm font-semibold text-slate-900">{{ inc.title || '-' }}</div>
              <div class="mt-1 text-sm text-slate-500">{{ inc.description || '-' }}</div>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase text-amber-700">
              {{ inc.status || '-' }}
            </span>
          </div>

          <dl class="mt-4 grid gap-4 text-sm text-slate-700 sm:grid-cols-2">
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Machine / Asset</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ inc.item?.name || '-' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Location</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ inc.location?.name || '-' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Handled By</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ inc.assigned_to?.name || '-' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Investigating Since</dt>
              <dd class="mt-1 font-medium text-slate-900">{{ formatDate(inc.investigating_started_at) }}</dd>
            </div>
          </dl>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Investigation Form</h2>
              <p class="text-sm text-slate-500">Review and update the investigation details below.</p>
            </div>
            <div class="text-xs text-slate-500">Draft saved in investigation_notes</div>
          </div>

          <div
            v-if="statusMessage"
            class="mt-4 rounded-lg border px-4 py-3 text-sm"
            :class="statusTone === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'"
          >
            {{ statusMessage }}
          </div>

          <div class="mt-5 space-y-4">
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Temporary Findings</label>
              <textarea
                v-model="form.investigation_notes"
                rows="8"
                class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
                placeholder="Record the results of sensor checks, machine logs, operator interviews, and field observations."
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Root Cause Hypothesis</label>
              <textarea
                v-model="form.root_cause_hypothesis"
                rows="6"
                class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
                placeholder="Example: Worn bearing causing high vibration and temperature increase."
              />
              <p class="mt-2 text-xs text-slate-500">When saving a draft, this field can be left empty. When submitting a proposal, this field is required.</p>
            </div>

            <div class="flex flex-wrap gap-3">
              <button
                type="button"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                :disabled="isSubmitting"
                @click="openConfirm('draft')"
              >
                Save Draft
              </button>
              <button
                type="button"
                class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50"
                :disabled="isSubmitting"
                @click="openConfirm('proposal')"
              >
                Submit Hypothesis to Supervisor
              </button>
            </div>
          </div>
        </div>
      </section>

      <aside class="space-y-6">
        <!-- Create Incident Attachment Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">1</span>
            <h2 class="text-base font-semibold text-slate-900">Create Incident</h2>
          </div>
          <p class="mt-1 text-xs text-slate-500">Attachment saat pelaporan awal insiden</p>
          <div class="mt-3">
            <AttachmentList
              v-if="createAttachments.length > 0"
              :attachments="createAttachments"
              :incident-id="inc.incident_id"
              :can-delete="true"
              @deleted="onAttachmentDeleted"
            />
            <div
              v-else
              class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center"
            >
              <p class="text-xs text-slate-500">Belum ada attachment</p>
            </div>
          </div>
        </div>

        <!-- Investigation Attachment Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
              <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-700">2</span>
              <h2 class="text-base font-semibold text-slate-900">Investigasi</h2>
            </div>
            <button
              v-if="!showUploader"
              type="button"
              class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700"
              @click="showUploader = true"
            >
              + Upload
            </button>
          </div>
          <p class="mt-1 text-xs text-slate-500">Upload Investigation Attachments</p>
          <div class="mt-3">
            <div v-if="showUploader" class="mb-3">
              <AttachmentUpload
                :incident-id="inc.incident_id"
                source-group="investigation"
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
              v-if="investigationAttachments.length > 0"
              :attachments="investigationAttachments"
              :incident-id="inc.incident_id"
              :can-delete="true"
              @deleted="onAttachmentDeleted"
            />
            <div
              v-else
              class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center"
            >
              <p class="text-xs text-slate-500">Belum ada attachment</p>
            </div>
          </div>
        </div>

        <!-- Other Stages Attachment Card (repair, verification, closing) -->
        <div
          v-if="otherAttachments.length > 0"
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >
          <div class="flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">3</span>
            <h2 class="text-base font-semibold text-slate-900">Tahap Lainnya</h2>
          </div>
          <p class="mt-1 text-xs text-slate-500">Attachment dari repair, verification, atau closing</p>
          <div class="mt-3 space-y-4">
            <!-- Repair -->
            <div v-if="repairAttachments.length > 0">
              <h4 class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-blue-600">
                <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                Repair
              </h4>
              <AttachmentList
                :attachments="repairAttachments"
                :incident-id="inc.incident_id"
                :can-delete="true"
                @deleted="onAttachmentDeleted"
              />
            </div>
            <!-- Verification -->
            <div v-if="verificationAttachments.length > 0">
              <h4 class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-emerald-600">
                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                Verifikasi
              </h4>
              <AttachmentList
                :attachments="verificationAttachments"
                :incident-id="inc.incident_id"
                :can-delete="true"
                @deleted="onAttachmentDeleted"
              />
            </div>
            <!-- Closing -->
            <div v-if="closingAttachments.length > 0">
              <h4 class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-purple-600">
                <span class="inline-block h-2 w-2 rounded-full bg-purple-500"></span>
                Closing
              </h4>
              <AttachmentList
                :attachments="closingAttachments"
                :incident-id="inc.incident_id"
                :can-delete="true"
                @deleted="onAttachmentDeleted"
              />
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-base font-semibold text-slate-900">Checklist Investigation</h2>
          <ul class="mt-4 space-y-3 text-sm text-slate-700">
            <li class="rounded-lg bg-slate-50 px-3 py-2">Check machine and sensor logs.</li>
            <li class="rounded-lg bg-slate-50 px-3 py-2">Conduct interviews with operators and field witnesses.</li>
            <li class="rounded-lg bg-slate-50 px-3 py-2">Document temporary findings before finalizing the hypothesis.</li>
            <li class="rounded-lg bg-slate-50 px-3 py-2">Submit a proposal only if the hypothesis is clear.</li>
          </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-base font-semibold text-slate-900">Summary Timeline</h2>
          <div class="mt-4 space-y-3 text-sm text-slate-700">
            <div v-for="log in (inc.audit_logs || []).slice(0, 5)" :key="log.log_id" class="rounded-lg border border-slate-200 p-3">
              <div class="font-semibold text-slate-900">{{ (log.action || '').replace(/_/g, ' ') }}</div>
              <div class="mt-1 text-xs text-slate-500">{{ formatDate(log.created_at) }}</div>
              <div v-if="log.action_details" class="mt-2 text-xs text-slate-600">{{ log.action_details }}</div>
            </div>
            <div v-if="!(inc.audit_logs || []).length" class="text-slate-500">No audit trail available.</div>
          </div>
        </div>
      </aside>
    </div>

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
  </AuthenticatedLayout>
</template>