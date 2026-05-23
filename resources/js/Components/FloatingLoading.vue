<script setup>
import { computed } from 'vue';

const props = defineProps({
  size: {
    type: Number,
    default: 56,
  },
  dotCount: {
    type: Number,
    default: 12,
  },
  label: {
    type: String,
    default: 'Loading',
  },
});

const dots = computed(() => Array.from({ length: props.dotCount }, (_, index) => index));
</script>

<template>
  <div
    class="floating-loader"
    :style="{ '--loader-size': `${size}px`, '--dot-count': dotCount }"
    role="status"
    :aria-label="label"
  >
    <span class="sr-only">{{ label }}</span>
    <div class="floating-loader__orbit">
      <span
        v-for="dot in dots"
        :key="dot"
        class="floating-loader__dot"
        :style="{ '--dot-index': dot }"
      />
    </div>
  </div>
</template>

<style scoped>
.floating-loader {
  width: var(--loader-size);
  height: var(--loader-size);
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  animation: floating-loader-bob 2.4s ease-in-out infinite;
}

.floating-loader__orbit {
  position: relative;
  width: 100%;
  height: 100%;
  animation: floating-loader-spin 1.2s linear infinite;
}

.floating-loader__dot {
  position: absolute;
  top: 50%;
  left: 50%;
  width: calc(var(--loader-size) * 0.11);
  height: calc(var(--loader-size) * 0.11);
  margin-left: calc(var(--loader-size) * -0.055);
  margin-top: calc(var(--loader-size) * -0.055);
  border-radius: 9999px;
  background: currentColor;
  opacity: 0.18;
  transform:
    rotate(calc(var(--dot-index) * (360deg / var(--dot-count))))
    translateY(calc(var(--loader-size) * -0.39));
}

.floating-loader__dot:nth-child(3n) {
  opacity: 0.42;
}

.floating-loader__dot:nth-child(4n) {
  opacity: 0.65;
}

@keyframes floating-loader-spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

@keyframes floating-loader-bob {
  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-6px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .floating-loader,
  .floating-loader__orbit {
    animation: none;
  }
}
</style>