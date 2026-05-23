<script setup>
import MasterDataIndex from './Index.vue';

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
</script>

<template>
  <MasterDataIndex v-bind="props" />
</template>
