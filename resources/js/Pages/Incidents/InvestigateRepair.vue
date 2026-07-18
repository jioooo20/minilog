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
const messageTone = ref('info');
const messageText = ref('');
const confirmOpen = ref(false);
const confirmAction = ref('');

const confirmTitle = computed(() => {
	return confirmAction.value === 'complete' ? 'Complete Repair?' : 'Save Repair Draft?';
});

const confirmMessage = computed(() => {
	return confirmAction.value === 'complete'
		? 'Data repair will be locked and the incident will be moved to the verification stage.'
		: 'Repair draft will be saved without changing the workflow status.';
});

const confirmText = computed(() => {
	return confirmAction.value === 'complete' ? 'Yes, complete repair' : 'Yes, save draft';
});

const confirmClass = computed(() => {
	return confirmAction.value === 'complete'
		? 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500'
		: 'bg-slate-900 hover:bg-slate-700 focus:ring-slate-500';
});

const form = reactive({
	corrective_actions: inc.value.corrective_actions ?? '',
	parts_replaced: inc.value.parts_replaced ?? '',
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

const saveDraft = async () => {
	isSubmitting.value = true;
	try {
		const response = await axios.patch(route('incidents.repair-draft', inc.value.incident_id), {
			corrective_actions: form.corrective_actions,
			parts_replaced: form.parts_replaced,
		});

		form.corrective_actions = response.data?.corrective_actions ?? form.corrective_actions;
		form.parts_replaced = response.data?.parts_replaced ?? form.parts_replaced;
		setMessage('success', 'Repair draft saved.');
	} catch (error) {
		console.error('saveDraft repair error', error);
		setMessage('error', error.response?.data?.message || 'Failed to save repair draft.');
	} finally {
		isSubmitting.value = false;
	}
};

const submitRepair = async () => {
	if (!form.corrective_actions.trim()) {
		setMessage('error', 'Corrective actions is required.');
		return;
	}

	isSubmitting.value = true;
	try {
		await axios.post(route('incidents.complete-repair', inc.value.incident_id), {
			corrective_actions: form.corrective_actions,
			parts_replaced: form.parts_replaced,
		});

		router.visit(route('incidents.show', inc.value.incident_id));
	} catch (error) {
		console.error('submitRepair error', error);
		setMessage('error', error.response?.data?.message || 'Failed to complete repair.');
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

	if (confirmAction.value === 'complete') {
		await submitRepair();
		return;
	}

	await saveDraft();
};
</script>

<template>
	<Head :title="`Repair Actions ${inc.incident_code || ''}`" />

	<AuthenticatedLayout>
		<template #header>
			<div class="flex items-center justify-between gap-3">
				<div>
					<p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Repair Actions</p>
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
						<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase text-emerald-700">
							{{ inc.status || '-' }}
						</span>
					</div>

					<dl class="mt-4 grid gap-4 text-sm text-slate-700 sm:grid-cols-2">
						<div>
							<dt class="text-xs uppercase tracking-wide text-slate-500">Corrective Actions</dt>
							<dd class="mt-1 font-medium text-slate-900">{{ inc.corrective_actions || '-' }}</dd>
						</div>
						<div>
							<dt class="text-xs uppercase tracking-wide text-slate-500">Parts Replaced</dt>
							<dd class="mt-1 font-medium text-slate-900">{{ inc.parts_replaced || '-' }}</dd>
						</div>
						<div>
							<dt class="text-xs uppercase tracking-wide text-slate-500">Repair Started At</dt>
							<dd class="mt-1 font-medium text-slate-900">{{ formatDate(inc.repair_started_at) }}</dd>
						</div>
						<div>
							<dt class="text-xs uppercase tracking-wide text-slate-500">Resolved At</dt>
							<dd class="mt-1 font-medium text-slate-900">{{ formatDate(inc.resolved_at) }}</dd>
						</div>
					</dl>
				</div>

				<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
					<div class="flex items-center justify-between gap-3">
						<div>
							<h2 class="text-base font-semibold text-slate-900">Repair Form</h2>
							<p class="text-sm text-slate-500">Fill in the repair actions and parts replaced. Save draft at any time.</p>
						</div>
						<div class="text-xs text-slate-500">Final submit will move to verifying</div>
					</div>

					<div
						v-if="messageText"
						class="mt-4 rounded-lg border px-4 py-3 text-sm"
						:class="messageTone === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'"
					>
						{{ messageText }}
					</div>

					<div class="mt-5 space-y-4">
						<div>
							<label class="mb-2 block text-sm font-medium text-slate-700">Corrective Actions</label>
							<textarea
								v-model="form.corrective_actions"
								rows="8"
								class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
								placeholder="Describe the repair actions taken."
							/>
						</div>

						<div>
							<label class="mb-2 block text-sm font-medium text-slate-700">Parts Replaced</label>
							<textarea
								v-model="form.parts_replaced"
								rows="4"
								class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
								placeholder="Optional: list the parts replaced, one per line or in a free-form format."
							/>
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
								class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
								:disabled="isSubmitting"
								@click="openConfirm('complete')"
							>
								Complete Repair, Ready for Verification
							</button>
						</div>
					</div>
				</div>
			</section>

			<aside class="space-y-6">
        <!-- Attachment Upload -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Before/After Photos</h2>
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
                source-group="repair"
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
          <h2 class="text-base font-semibold text-slate-900">Checklist Repair</h2>
					<ul class="mt-4 space-y-3 text-sm text-slate-700">
						<li class="rounded-lg bg-slate-50 px-3 py-2">Ensure the repair is in line with the approved hypothesis.</li>
						<li class="rounded-lg bg-slate-50 px-3 py-2">Document the repair actions clearly.</li>
						<li class="rounded-lg bg-slate-50 px-3 py-2">Fill in parts_replaced if any parts were replaced.</li>
						<li class="rounded-lg bg-slate-50 px-3 py-2">Final submit will record timestamps and move to verifying.</li>
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
