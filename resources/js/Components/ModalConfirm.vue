<script setup>
import FloatingLoading from '@/Components/FloatingLoading.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
	show: {
		type: Boolean,
		default: false,
	},
	title: {
		type: String,
		default: 'Confirmation',
	},
	message: {
		type: String,
		default: '',
	},
	confirmText: {
		type: String,
		default: 'Yes, continue',
	},
	cancelText: {
		type: String,
		default: 'Cancel',
	},
	confirmClass: {
		type: String,
		default: 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500',
	},
	processing: {
		type: Boolean,
		default: false,
	},
	maxWidth: {
		type: String,
		default: 'lg',
	},
	closeable: {
		type: Boolean,
		default: true,
	},
});

const emit = defineEmits(['close', 'confirm']);

const handleClose = () => {
	if (!props.processing && props.closeable) {
		emit('close');
	}
};

const handleConfirm = () => {
	if (!props.processing) {
		emit('confirm');
	}
};
</script>

<template>
	<Modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="handleClose">
		<div class="p-6">
			<h2 class="text-lg font-semibold text-slate-900">
				{{ title }}
			</h2>

			<p v-if="message" class="mt-2 text-sm leading-6 text-slate-600">
				{{ message }}
			</p>

			<div v-if="$slots.default" class="mt-4 text-sm leading-6 text-slate-600">
				<slot />
			</div>

			<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
				<SecondaryButton :disabled="processing" @click="handleClose">
					{{ cancelText }}
				</SecondaryButton>

				<button
					type="button"
					class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
					:class="confirmClass"
					:disabled="processing"
					@click="handleConfirm"
				>
					<FloatingLoading v-if="processing" class="mr-2" :size="14" label="Loading" />
					{{ confirmText }}
				</button>
			</div>
		</div>
	</Modal>
</template>
