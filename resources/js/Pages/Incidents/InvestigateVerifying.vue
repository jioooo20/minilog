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
const isClosingRequested = computed(() => Boolean(inc.value.closing_requested));
const confirmOpen = ref(false);
const confirmAction = ref('');

const confirmTitle = computed(() => {
	return confirmAction.value === 'pass' ? 'Confirm Successful Verification?' : 'Confirm Failed Verification?';
});

const confirmMessage = computed(() => {
	return confirmAction.value === 'pass'
		? 'The incident will remain in the verifying stage until the supervisor closes it.'
		: 'Status will return to repairing for further improvement.';
});

const confirmText = computed(() => {
	return confirmAction.value === 'pass' ? 'Yes, verification successful' : 'Yes, verification failed';
});

const confirmClass = computed(() => {
	return confirmAction.value === 'pass'
		? 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500'
		: 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500';
});

const form = reactive({
	passed: true,
	verification_notes: inc.value.verification_notes ?? '',
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

const submitVerification = async (passed) => {
	if (!passed && !form.verification_notes.trim()) {
		setMessage('error', 'Notes are required if verification fails.');
		return;
	}

	isSubmitting.value = true;
	try {
		if (isClosingRequested.value) {
			setMessage('error', 'Closing has been requested, verification cannot be proceed.');
			return; 
		}

		await axios.post(route('incidents.verify', inc.value.incident_id), {
			passed,
			verification_notes: form.verification_notes,
		});

		router.visit(route('incidents.show', inc.value.incident_id));
	} catch (error) {
		console.error('submitVerification error', error);
		setMessage('error', error.response?.data?.message || 'Failed to process verification.');
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
	await submitVerification(confirmAction.value === 'pass');
};
</script>

<template>
	<Head :title="`Verifikasi ${inc.incident_code || ''}`" />

	<AuthenticatedLayout>
		<template #header>
			<div class="flex items-center justify-between gap-3">
				<div>
					<p class="text-xs font-semibold uppercase tracking-wide text-violet-600">Operator Verification</p>
					<h1 class="text-lg font-semibold text-slate-900">{{ inc.incident_code || '-' }}</h1>
				</div>
				<span
					v-if="isClosingRequested"
					class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase text-indigo-700"
				>
					Closing has been requested
				</span>
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
						<span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold uppercase text-violet-700">
							{{ inc.status || '-' }}
						</span>
					</div>
					<div v-if="isClosingRequested" class="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
						Closing has been requested. Verification is frozen until the supervisor closes the incident.
					</div>

					<dl class="mt-4 grid gap-4 text-sm text-slate-700 sm:grid-cols-2">
						<div>
							<dt class="text-xs uppercase tracking-wide text-slate-500">Repair Completed At</dt>
							<dd class="mt-1 font-medium text-slate-900">{{ formatDate(inc.resolved_at) }}</dd>
						</div>
						<div>
							<dt class="text-xs uppercase tracking-wide text-slate-500">Verified By</dt>
							<dd class="mt-1 font-medium text-slate-900">{{ inc.verified_by?.name || '-' }}</dd>
						</div>
						<div>
							<dt class="text-xs uppercase tracking-wide text-slate-500">Verification At</dt>
							<dd class="mt-1 font-medium text-slate-900">{{ formatDate(inc.verified_at) }}</dd>
						</div>
						<div>
							<dt class="text-xs uppercase tracking-wide text-slate-500">Handled By</dt>
							<dd class="mt-1 font-medium text-slate-900">{{ inc.assigned_to?.name || '-' }}</dd>
						</div>
					</dl>
				</div>

				<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
					<div class="flex items-center justify-between gap-3">
						<div>
							<h2 class="text-base font-semibold text-slate-900">Verification Form</h2>
							<p class="text-sm text-slate-500">Operator evaluates whether the machine returns to normal operation after repair.</p>
						</div>
						<div class="text-xs text-slate-500">Status remains verifying until the supervisor closes the incident</div>
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
							<label class="mb-2 block text-sm font-medium text-slate-700">Verification Notes</label>
							<textarea
								v-model="form.verification_notes"
								rows="6"
								class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
								placeholder="Catatan hasil test mesin setelah repair. Wajib diisi jika verifikasi gagal."
							/>
						</div>

						<div class="flex flex-wrap gap-3">
							<button
								type="button"
								class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
								:disabled="isSubmitting || isClosingRequested"
								@click="openConfirm('pass')"
							>
								Verification Successful
							</button>
							<button
								type="button"
								class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
								:disabled="isSubmitting || isClosingRequested"
								@click="openConfirm('fail')"
							>
								Verification Failed
							</button>
						</div>
					</div>
				</div>
			</section>

			<aside class="space-y-6">
        <!-- Attachment Upload -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Verification Attachments</h2>
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
                source-group="verification"
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
          <h2 class="text-base font-semibold text-slate-900">Checkpoint Verification</h2>
					<ul class="mt-4 space-y-3 text-sm text-slate-700">
						<li class="rounded-lg bg-slate-50 px-3 py-2">Ensure the machine has been started and tested.</li>
						<li class="rounded-lg bg-slate-50 px-3 py-2">Fill in notes if there are any remaining issues.</li>
						<li class="rounded-lg bg-slate-50 px-3 py-2">If successful, status remains verifying until the supervisor closes the incident.</li>
						<li class="rounded-lg bg-slate-50 px-3 py-2">If failed, status returns to repairing.</li>
					</ul>
				</div>

				<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
					<h2 class="text-base font-semibold text-slate-900">Repair Summary</h2>
					<div class="mt-4 space-y-3 text-sm text-slate-700">
						<div class="rounded-xl border border-slate-200 p-3">
							<div class="text-xs uppercase tracking-wide text-slate-500">Corrective Actions</div>
							<div class="mt-1 whitespace-pre-line">{{ inc.corrective_actions || '-' }}</div>
						</div>
						<div class="rounded-xl border border-slate-200 p-3">
							<div class="text-xs uppercase tracking-wide text-slate-500">Parts Replaced</div>
							<div class="mt-1 whitespace-pre-line">{{ inc.parts_replaced || '-' }}</div>
						</div>
					</div>
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
