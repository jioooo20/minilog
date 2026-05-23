<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  type: { type: String, default: 'info' }, // success | error | info
  message: { type: [String, Object], default: '' },
  closable: { type: Boolean, default: true },
  timeout: { type: Number, default: 5000 },
});

const visible = ref(true);

const close = () => {
  visible.value = false;
};

onMounted(() => {
  if (props.timeout && props.timeout > 0) {
    setTimeout(() => (visible.value = false), props.timeout);
  }
});

const tone = (type) => {
  switch (type) {
    case 'success':
      return {
        bg: 'bg-emerald-50',
        border: 'border-emerald-200',
        text: 'text-emerald-800',
      };
    case 'error':
      return {
        bg: 'bg-rose-50',
        border: 'border-rose-200',
        text: 'text-rose-800',
      };
    default:
      return {
        bg: 'bg-sky-50',
        border: 'border-sky-200',
        text: 'text-sky-800',
      };
  }
};

</script>

<template>
  <div v-if="visible" :class="[`rounded-md border px-4 py-3`, tone(props.type).bg, tone(props.type).border]">
    <div class="flex items-start justify-between gap-3">
      <div class="flex items-center gap-3">
        <svg v-if="props.type === 'success'" class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <svg v-else-if="props.type === 'error'" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <svg v-else class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" />
        </svg>

        <div>
          <div :class="[tone(props.type).text, 'text-sm font-medium']">
            <slot>
              <span v-if="typeof props.message === 'string'">{{ props.message }}</span>
              <span v-else v-html="props.message?.toString()" />
            </slot>
          </div>
        </div>
      </div>

      <div v-if="props.closable">
        <button type="button" class="text-slate-400 hover:text-slate-600" @click="close">
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>
