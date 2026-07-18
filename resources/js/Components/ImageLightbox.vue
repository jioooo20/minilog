<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  images: { type: Array, default: () => [] },
  initialIndex: { type: Number, default: 0 },
});

const emit = defineEmits(['close']);

const currentIndex = ref(props.initialIndex);

watch(
  () => props.initialIndex,
  (val) => {
    currentIndex.val = val;
  }
);

watch(
  () => props.show,
  (val) => {
    if (val) {
      currentIndex.value = props.initialIndex;
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  }
);

const currentImage = (idx) => props.images[idx] ?? null;

const goPrev = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--;
  }
};

const goNext = () => {
  if (currentIndex.value < props.images.length - 1) {
    currentIndex.value++;
  }
};

const close = () => {
  document.body.style.overflow = '';
  emit('close');
};

const handleKeydown = (e) => {
  if (!props.show) return;
  if (e.key === 'Escape') close();
  if (e.key === 'ArrowLeft') goPrev();
  if (e.key === 'ArrowRight') goNext();
};

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80"
      @click.self="close"
    >
      <!-- Close button -->
      <button
        class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70"
        @click="close"
        aria-label="Close"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Counter -->
      <div class="absolute left-4 top-4 z-10 rounded-full bg-black/50 px-3 py-1 text-sm text-white">
        {{ currentIndex + 1 }} / {{ images.length }}
      </div>

      <!-- Prev button -->
      <button
        v-if="images.length > 1 && currentIndex > 0"
        class="absolute left-4 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70"
        @click="goPrev"
        aria-label="Previous"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <!-- Next button -->
      <button
        v-if="images.length > 1 && currentIndex < images.length - 1"
        class="absolute right-4 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70"
        @click="goNext"
        aria-label="Next"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
      </button>

      <!-- Image -->
      <div class="flex max-h-[90vh] max-w-[90vw] items-center justify-center">
        <img
          v-if="currentImage(currentIndex)"
          :src="currentImage(currentIndex).url"
          :alt="currentImage(currentIndex).file_name"
          class="max-h-[85vh] max-w-[90vw] rounded-lg object-contain shadow-2xl"
        />
      </div>

      <!-- Caption -->
      <div
        v-if="currentImage(currentIndex)?.description || currentImage(currentIndex)?.file_name"
        class="absolute bottom-4 left-1/2 z-10 max-w-lg -translate-x-1/2 rounded-lg bg-black/60 px-4 py-2 text-center text-sm text-white"
      >
        <div class="font-medium">{{ currentImage(currentIndex)?.file_name }}</div>
        <div v-if="currentImage(currentIndex)?.description" class="mt-1 text-xs text-white/80">
          {{ currentImage(currentIndex)?.description }}
        </div>
      </div>
    </div>
  </Teleport>
</template>
