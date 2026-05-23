<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
const reviewTone = ref('info');
const reviewMessage = ref('');
const confirmOpen = ref(false);
const confirmAction = ref('');

const confirmTitle = computed(() => {
	return confirmAction.value === 'approve' ? 'Approve Hypothesis?' : 'Reject Hypothesis?';
});

const confirmMessage = computed(() => {
	return confirmAction.value === 'approve'
		? 'Hypothesis will be forwarded to the repair stage.'
		: 'Hypothesis will be returned to the engineer for revision.';
});

const confirmText = computed(() => {
	return confirmAction.value === 'approve' ? 'Yes, approve' : 'Yes, reject';
});

const confirmClass = computed(() => {
	return confirmAction.value === 'approve'
		? 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500'
		: 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500';
});

const form = reactive({
	hypothesis_review_notes: inc.value.hypothesis_review_notes ?? '',
});

const formatDate = (value) => {
	if (!value) return '-';
	const date = new Date(value);
	if (Number.isNaN(date.getTime())) return value;
	return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
};

const setMessage = (tone, message) => {
	reviewTone.value = tone;
	reviewMessage.value = message;
};

const submitReview = async (action) => {
	if (action === 'reject' && !form.hypothesis_review_notes.trim()) {
		setMessage('error', 'Review notes are required when rejecting the hypothesis.');
		return;
	}

	isSubmitting.value = true;
	try {
		const endpoint = action === 'approve' ? route('incidents.approve', inc.value.incident_id) : route('incidents.reject', inc.value.incident_id);

		await axios.post(endpoint, {
			hypothesis_review_notes: form.hypothesis_review_notes,
		});

		router.visit(route('incidents.show', inc.value.incident_id));
	} catch (error) {
		console.error('submitReview error', error);
		setMessage('error', error.response?.data?.message || 'Failed to process hypothesis review.');
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
	await submitReview(confirmAction.value);
};
</script>

<template>
	<Head :title="`Review Hipotesis ${inc.incident_code || ''}`" />

	<AuthenticatedLayout>
		<template #header>
			<div class="flex flex-wrap items-center justify-between gap-3">
				<div>
					<p class="text-xs font-semibold uppercase tracking-wide text-sky-600">Supervisor Review</p>
					<h1 class="text-lg font-semibold text-slate-900">{{ inc.incident_code || '-' }}</h1>
					<p class="text-sm text-slate-500">Review the engineer's hypothesis, then approve or reject with review comments.</p>
				</div>
				<Link
					:href="route('incidents.show', inc.incident_id)"
					class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
				>
					Return to Details
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
						<span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase text-sky-700">
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
							<dt class="text-xs uppercase tracking-wide text-slate-500">Proposal Time</dt>
							<dd class="mt-1 font-medium text-slate-900">{{ formatDate(inc.updated_at) }}</dd>
						</div>
					</dl>
				</div>

				<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
					<div class="flex items-center justify-between gap-3">
						<div>
							<h2 class="text-base font-semibold text-slate-900">Engineer's Hypothesis</h2>
							<p class="text-sm text-slate-500">Read the temporary findings and root cause hypothesis before making a decision.</p>
						</div>
					</div>

					<div class="mt-5 grid gap-4 md:grid-cols-2">
						<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
							<div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Investigation Notes</div>
							<div class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ inc.investigation_notes || 'No investigation notes available.' }}</div>
						</div>
						<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
							<div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Root Cause Hypothesis</div>
							<div class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ inc.root_cause_hypothesis || 'No root cause hypothesis available.' }}</div>
						</div>
					</div>
				</div>

				<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
					<h2 class="text-base font-semibold text-slate-900">Review Form</h2>
					<p class="mt-1 text-sm text-slate-500">These notes are used when approving or rejecting. If rejecting, notes are required.</p>

					<div
						v-if="reviewMessage"
						class="mt-4 rounded-lg border px-4 py-3 text-sm"
						:class="reviewTone === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'"
					>
						{{ reviewMessage }}
					</div>

					<div class="mt-5">
						<label class="mb-2 block text-sm font-medium text-slate-700">Comment / Review Notes</label>
						<textarea
							v-model="form.hypothesis_review_notes"
							rows="6"
							class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
							placeholder="Write the reason for approval or the points that need to be improved if rejecting the hypothesis."
						/>
					</div>

					<div class="mt-5 flex flex-wrap gap-3">
						<button
							type="button"
							class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
							:disabled="isSubmitting"
								@click="openConfirm('approve')"
						>
							Approve Hypothesis
						</button>
						<button
							type="button"
							class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
							:disabled="isSubmitting"
								@click="openConfirm('reject')"
						>
							Reject Hypothesis
						</button>
					</div>
				</div>
			</section>

			<aside class="space-y-6">
				<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
					<h2 class="text-base font-semibold text-slate-900">Review Decision</h2>
					<div class="mt-4 space-y-3 text-sm text-slate-700">
						<div class="rounded-lg bg-emerald-50 px-3 py-2">Approve: status becomes <span class="font-semibold">repairing</span>.</div>
						<div class="rounded-lg bg-rose-50 px-3 py-2">Reject: status returns to <span class="font-semibold">investigating</span>.</div>
						<div class="rounded-lg bg-slate-50 px-3 py-2">Audit log is automatically recorded for both actions.</div>
					</div>
				</div>

				<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
					<h2 class="text-base font-semibold text-slate-900">Last Review Notes</h2>
					<div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
						{{ inc.hypothesis_review_notes || 'No previous review notes available.' }}
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
