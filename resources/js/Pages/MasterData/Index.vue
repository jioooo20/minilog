<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FloatingLoading from '@/Components/FloatingLoading.vue';
import Modal from '@/Components/Modal.vue';
import ModalConfirm from '@/Components/ModalConfirm.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
	activeType: { type: String, required: true },
	types: { type: Array, default: () => [] },
	records: { type: Object, default: () => ({ data: [], links: [] }) },
	filters: { type: Object, default: () => ({}) },
	columns: { type: Array, default: () => [] },
	fields: { type: Array, default: () => [] },
	typeMeta: { type: Object, default: () => ({}) },
	canManage: { type: Boolean, default: false },
	options: { type: Object, default: () => ({}) },
});

const currentQuery = reactive({
	type: props.activeType,
	state: props.filters.state ?? 'active',
	search: props.filters.search ?? '',
	per_page: props.filters.per_page ?? 10,
});

const formOpen = ref(false);
const formMode = ref('create');
const processing = ref(false);
const validationErrors = ref({});
const editingRecord = ref(null);
const confirmOpen = ref(false);
const confirmMode = ref('delete');
const targetRecord = ref(null);

const form = reactive({});

const recordsData = computed(() => props.records?.data ?? []);
const paginatorLinks = computed(() => props.records?.links ?? []);
const activeTypeLabel = computed(() => props.typeMeta?.label ?? 'Master Data');

const seedForm = (record = {}) => {
	props.fields.forEach((field) => {
		if (field.type === 'checkbox') {
			form[field.key] = Boolean(record[field.key] ?? false);
			return;
		}

		form[field.key] = record[field.key] ?? '';
	});
};

watch(
	() => props.fields,
	() => seedForm(),
	{ immediate: true, deep: true }
);

const refreshCurrent = () => {
	router.get(route('master-data.index'), {
		type: currentQuery.type,
		state: currentQuery.state || undefined,
		search: currentQuery.search || undefined,
		per_page: currentQuery.per_page,
	}, {
		preserveState: true,
		preserveScroll: true,
		replace: true,
	});
};

const selectType = (type) => {
	currentQuery.type = type;
	router.get(route('master-data.index'), {
		type,
		state: currentQuery.state || undefined,
		search: currentQuery.search || undefined,
		per_page: currentQuery.per_page,
	}, {
		preserveState: true,
		preserveScroll: true,
		replace: true,
	});
};

const applyFilters = () => refreshCurrent();

const resetFilters = () => {
	currentQuery.state = 'active';
	currentQuery.search = '';
	refreshCurrent();
};

const openCreate = () => {
	formMode.value = 'create';
	editingRecord.value = null;
	validationErrors.value = {};
	seedForm();
	formOpen.value = true;
};

const openEdit = (record) => {
	formMode.value = 'edit';
	editingRecord.value = record;
	validationErrors.value = {};
	seedForm(record);
	formOpen.value = true;
};

const closeForm = () => {
	if (processing.value) return;
	formOpen.value = false;
	validationErrors.value = {};
};

const getOptions = (key) => props.options?.[key] ?? [];

const buildPayload = () => {
	const payload = {};

	props.fields.forEach((field) => {
		const value = form[field.key];

		if (field.type === 'checkbox') {
			payload[field.key] = Boolean(value);
			return;
		}

		payload[field.key] = value === '' ? null : value;
	});

	return payload;
};

const submitForm = async () => {
	processing.value = true;
	validationErrors.value = {};

	try {
		const payload = buildPayload();

		if (formMode.value === 'create') {
			await axios.post(route('master-data.store', currentQuery.type), payload);
		} else {
			await axios.patch(route('master-data.update', { type: currentQuery.type, record: editingRecord.value.id }), payload);
		}

		formOpen.value = false;
		router.reload({ preserveScroll: true, preserveState: true });
	} catch (error) {
		const responseErrors = error?.response?.data?.errors ?? {};
		validationErrors.value = responseErrors;
	} finally {
		processing.value = false;
	}
};

const askDelete = (record) => {
	confirmMode.value = 'delete';
	targetRecord.value = record;
	confirmOpen.value = true;
};

const askRestore = (record) => {
	confirmMode.value = 'restore';
	targetRecord.value = record;
	confirmOpen.value = true;
};

const confirmTitle = computed(() => confirmMode.value === 'delete' ? 'Deactivate master data?' : 'Restore master data?');
const confirmMessage = computed(() => {
	if (!targetRecord.value) return '';
	const name = targetRecord.value.item_name || targetRecord.value.location_name || targetRecord.value.dept_name || targetRecord.value.category_name || targetRecord.value.asset_tag || targetRecord.value.location_code || targetRecord.value.dept_code || targetRecord.value.category_code || 'record';
	return confirmMode.value === 'delete'
		? `Record "${name}" will be deactivated.`
		: `Record "${name}" will be reactivated.`;
});

const runConfirm = async () => {
	if (!targetRecord.value) return;

	processing.value = true;

	try {
		const url = confirmMode.value === 'delete'
			? route('master-data.destroy', { type: currentQuery.type, record: targetRecord.value.id })
			: route('master-data.restore', { type: currentQuery.type, record: targetRecord.value.id });

		if (confirmMode.value === 'delete') {
			await axios.delete(url);
		} else {
			await axios.post(url);
		}

		confirmOpen.value = false;
		targetRecord.value = null;
		router.reload({ preserveScroll: true, preserveState: true });
	} finally {
		processing.value = false;
	}
};

const formatCell = (record, key) => {
	const value = record[key];

	if (key === 'is_active') {
		return value ? 'Active' : 'Inactive';
	}

	if (value === null || value === undefined || value === '') return '-';
	return value;
};

const recordTone = (record) => {
	if (record.is_active === false) return 'bg-amber-50 border-amber-200';
	return 'bg-white border-slate-200';
};

const typeCounts = computed(() => props.types || []);
</script>

<template>
	<Head title="Master Data" />

	<AuthenticatedLayout>
		<template #header>
			<div class="flex items-center justify-between gap-3">
				<div>
					<p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Master data</p>
					<h1 class="text-xl font-semibold text-slate-900">{{ activeTypeLabel }}</h1>
					<p class="mt-1 text-sm text-slate-500">Manage master data through type tabs, then filter/search, then the detail table below.</p>
				</div>

				<button
					v-if="canManage"
					type="button"
					@click="openCreate"
					class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white shadow-sm hover:bg-slate-700"
				>
					Add {{ activeTypeLabel }}
				</button>
			</div>
		</template>

		<div class="space-y-6">
			<section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
				<button
					v-for="tab in typeCounts"
					:key="tab.type"
					type="button"
					@click="selectType(tab.type)"
					class="rounded-2xl border p-4 text-left shadow-sm transition hover:-translate-y-0.5"
					:class="tab.type === activeType ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-900'"
				>
					<div class="text-xs font-semibold uppercase tracking-wide" :class="tab.type === activeType ? 'text-slate-300' : 'text-slate-500'">{{ tab.label }}</div>
					<div class="mt-2 text-3xl font-semibold">{{ tab.count }}</div>
					<div class="mt-1 text-sm" :class="tab.type === activeType ? 'text-slate-200' : 'text-slate-500'">{{ tab.description }}</div>
					<div class="mt-3 text-xs uppercase tracking-wide" :class="tab.type === activeType ? 'text-slate-300' : 'text-slate-500'">Inactive: {{ tab.inactive }}</div>
				</button>
			</section>

			<section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
				<div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
					<div>
						<h2 class="text-base font-semibold text-slate-900">Filter & Search</h2>
						<p class="text-sm text-slate-500">Filter active, inactive, or all records.</p>
					</div>

					<div class="flex flex-wrap gap-2">
						<button type="button" @click="resetFilters" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset</button>
						<button type="button" @click="applyFilters" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Apply</button>
					</div>
				</div>

				<div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
					<div>
						<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">State</label>
						<select v-model="currentQuery.state" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
							<option value="active">Active</option>
							<option value="inactive">Inactive</option>
							<option value="all">All</option>
						</select>
					</div>

					<div class="md:col-span-2">
						<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
						<input v-model="currentQuery.search" type="text" placeholder="Search current master data" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500" />
					</div>
				</div>
			</section>

			<section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
				<div class="flex items-center justify-between gap-3">
					<div>
						<h2 class="text-base font-semibold text-slate-900">{{ activeTypeLabel }} Table</h2>
						<p class="text-sm text-slate-500">Data table for the selected master data type.</p>
					</div>

					<div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700">
						{{ records.total ?? 0 }} records
					</div>
				</div>

				<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-slate-200">
							<thead class="bg-slate-100">
								<tr class="text-left text-xs uppercase tracking-wide text-slate-500">
									<th v-for="column in columns" :key="column.key" class="px-4 py-3">{{ column.label }}</th>
									<th class="px-4 py-3 text-right">Actions</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-slate-200 bg-white text-sm text-slate-700">
								<tr v-if="!recordsData.length">
									<td :colspan="columns.length + 1" class="px-4 py-8 text-center text-slate-500">No records found.</td>
								</tr>

								<tr v-for="record in recordsData" :key="record.id" :class="recordTone(record)">
									<td v-for="column in columns" :key="column.key" class="px-4 py-3 align-top">
										<span v-if="column.key === 'is_active'" :class="record.is_active ? 'rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700' : 'rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700'">
											{{ formatCell(record, column.key) }}
										</span>
										<span v-else>{{ formatCell(record, column.key) }}</span>
									</td>
									<td class="px-4 py-3 text-right">
										<div v-if="canManage" class="flex justify-end gap-2">
											<button
												v-if="record.is_active"
												type="button"
												@click="openEdit(record)"
												class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
											>
												Edit
											</button>
											<button
												v-if="record.is_active"
												type="button"
												@click="askDelete(record)"
												class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700"
											>
												Delete
											</button>
											<button
												v-else
												type="button"
												@click="askRestore(record)"
												class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
											>
												Restore
											</button>
										</div>
										<span v-else class="text-xs text-slate-400">Read only</span>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<div v-if="paginatorLinks.length" class="mt-5 flex flex-wrap items-center justify-center gap-2">
					<button
						v-for="link in paginatorLinks"
						:key="link.label"
						type="button"
						:disabled="!link.url"
						@click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true, replace: true })"
						:class="[
							'rounded-xl border px-3 py-2 text-sm font-medium',
							link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
							!link.url && 'pointer-events-none opacity-40',
						]"
						v-html="link.label"
					/>
				</div>
			</section>
		</div>

		<Modal :show="formOpen" max-width="2xl" @close="closeForm">
			<div class="p-6">
				<div class="flex items-start justify-between gap-3">
					<div>
						<h2 class="text-lg font-semibold text-slate-900">{{ formMode === 'create' ? 'Create' : 'Edit' }} {{ activeTypeLabel }}</h2>
						<p class="mt-1 text-sm text-slate-500">Fill in the required fields for the selected master data type.</p>
					</div>
					<button type="button" class="text-slate-400 hover:text-slate-700" @click="closeForm">✕</button>
				</div>

				<div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
					<div v-for="field in fields" :key="field.key" :class="field.type === 'textarea' ? 'md:col-span-2' : ''">
						<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ field.label }}</label>

						<template v-if="field.type === 'textarea'">
							<textarea v-model="form[field.key]" rows="4" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500" />
						</template>

						<template v-else-if="field.type === 'select'">
							<select v-model="form[field.key]" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
								<option value="">Select...</option>
								<option v-for="option in getOptions(field.options)" :key="option.value" :value="option.value">{{ option.label }}</option>
							</select>
						</template>

						<template v-else-if="field.type === 'checkbox'">
							<label class="flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700">
								<input v-model="form[field.key]" type="checkbox" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500" />
								<span>{{ field.label }}</span>
							</label>
						</template>

						<template v-else>
							<input v-model="form[field.key]" type="text" class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500" />
						</template>

						<p v-if="validationErrors[field.key]" class="mt-1 text-xs text-rose-600">{{ validationErrors[field.key][0] }}</p>
					</div>
				</div>

				<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
						<button type="button" @click="closeForm" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
							Cancel
					</button>
					<button type="button" @click="submitForm" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-60" :disabled="processing">
						<FloatingLoading v-if="processing" class="mr-2" :size="14" label="Saving" />
						Save
					</button>
				</div>
			</div>
		</Modal>

		<ModalConfirm
			:show="confirmOpen"
			:title="confirmTitle"
			:message="confirmMessage"
			:confirm-text="confirmMode === 'delete' ? 'Delete' : 'Restore'"
			:confirm-class="confirmMode === 'delete' ? 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500' : 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500'"
			:processing="processing"
			@close="confirmOpen = false"
			@confirm="runConfirm"
		/>
	</AuthenticatedLayout>
</template>
