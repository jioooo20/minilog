<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Alert from '@/Components/Alert.vue';

const page = usePage();

const flashes = computed(() => {
  const f = page.props.flash || {};
  // normalize to array of {type, message}
  const out = [];
  for (const key of Object.keys(f)) {
    const val = f[key];
    if (val === undefined || val === null || val === '') continue;
    if (Array.isArray(val)) {
      val.forEach((v) => out.push({ type: key, message: v }));
    } else {
      out.push({ type: key, message: val });
    }
  }
  return out;
});

const mapType = (key) => {
  // map common flash keys to Alert types
  if (['success', 'ok', 'done'].includes(key)) return 'success';
  if (['error', 'danger', 'failed'].includes(key)) return 'error';
  return 'info';
};
</script>

<template>
  <div v-if="flashes.length" class="space-y-2">
    <Alert
      v-for="(f, idx) in flashes"
      :key="idx"
      :type="mapType(f.type)"
      :message="f.message"
      :timeout="5000"
    />
  </div>
</template>
