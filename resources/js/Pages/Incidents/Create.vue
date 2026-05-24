<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ModalConfirm from '@/Components/ModalConfirm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  item_statuses: {
    type: Object,
    default: () => ({}),
  },
  locations: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  title: '',
  description: '',
  item_id: '',
  component_item_id: '',
  location_id: '',
  severity: 'Medium',
  detected_at: '',
});

const confirmOpen = ref(false);

const submit = () => {
  form.post(route('incidents.store'), {
    preserveScroll: true,
  });
};

const openConfirm = () => {
  confirmOpen.value = true;
};

const handleConfirm = async () => {
  confirmOpen.value = false;
  submit();
};

const getItemStatus = (item) => {
  return props.item_statuses?.[item?.item_id] ?? item?.status ?? item?.item_status ?? '';
};

const getItemLabel = (item) => {
  const status = getItemStatus(item);
  const locationName = item?.location_name;
  const locationSuffix = item?.location_id && locationName ? ` - ${locationName}` : '';

  return `${item?.item_name ?? '-'}${status !== 'operational' ? ` (${status || 'unknown'})` : ''}${locationSuffix}`;
};
</script>

<template>
  <Head title="Create Incident" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3">
        <div>
          <h1 class="text-lg font-semibold text-slate-900">Create Incident</h1>
          <p class="text-sm text-slate-500">Fill in the incident details as they occurred on the field.</p>
        </div>
        <Link
          :href="route('incidents.index')"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </Link>
      </div>
    </template>

    <form class="space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" @submit.prevent="openConfirm">
      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Incident Title</label>
        <input
          v-model="form.title"
          type="text"
          placeholder="Example: Conveyor machine stops unexpectedly"
          class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
        />
        <p v-if="form.errors.title" class="mt-1 text-xs text-rose-600">{{ form.errors.title }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Initial Description</label>
        <textarea
          v-model="form.description"
          rows="4"
          placeholder="Explain the symptoms, conditions, and time of occurrence"
          class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
        />
        <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-semibold text-slate-700">Machine / Asset</label>
          <select
            v-model="form.item_id"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          >
            <option value="">Pilih asset</option>
            <option
              v-for="item in props.items"
              :key="item.item_id"
              :value="item.item_id"
              :disabled="getItemStatus(item) !== 'operational'"
              :class="getItemStatus(item) !== 'operational' ? 'bg-rose-50 text-rose-700' : ''"
            >
              {{ getItemLabel(item) }}
            </option>
          </select>
          <p class="mt-1 text-xs text-slate-500">Non-operational assets are marked red and cannot be selected.</p>
          <p v-if="form.errors.item_id" class="mt-1 text-xs text-rose-600">{{ form.errors.item_id }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-semibold text-slate-700">Location</label>
          <select
            v-model="form.location_id"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          >
            <option value="">Pilih lokasi</option>
            <option v-for="location in props.locations" :key="location.location_id" :value="location.location_id">
              {{ location.location_name }}
            </option>
          </select>
          <p v-if="form.errors.location_id" class="mt-1 text-xs text-rose-600">{{ form.errors.location_id }}</p>
        </div>

        <!-- <div>
          <label class="mb-1 block text-sm font-semibold text-slate-700">Component (optional)</label>
          <select
            v-model="form.component_item_id"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          >
            <option value="">None</option>
            <option v-for="item in items" :key="`component-${item.item_id}`" :value="item.item_id">
              {{ item.item_name }}
            </option>
          </select>
          <p v-if="form.errors.component_item_id" class="mt-1 text-xs text-rose-600">{{ form.errors.component_item_id }}</p>
        </div> -->

        <div>
          <label class="mb-1 block text-sm font-semibold text-slate-700">Severity</label>
          <select
            v-model="form.severity"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          >
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
          </select>
          <p v-if="form.errors.severity" class="mt-1 text-xs text-rose-600">{{ form.errors.severity }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-semibold text-slate-700">Detected At (optional)</label>
          <input
            v-model="form.detected_at"
            type="datetime-local"
            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
          />
          <p v-if="form.errors.detected_at" class="mt-1 text-xs text-rose-600">{{ form.errors.detected_at }}</p>
        </div>
      </div>


      <div class="flex flex-wrap gap-2 pt-2">
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ form.processing ? 'Saving...' : 'Save Incident' }}
        </button>
        <button
          type="button"
          :disabled="form.processing"
          class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="form.reset()"
        >
          Reset
        </button>
      </div>
    </form>

    <ModalConfirm
      :show="confirmOpen"
      title="Confirm Incident Creation"
      message="Are you sure you want to create this incident? Please review the details before confirming."
      confirm-text="Yes, create it"
      cancel-text="No"
      :processing="form.processing"
      confirm-class="bg-slate-900 hover:bg-slate-700 focus:ring-slate-500"
      @close="confirmOpen = false"
      @confirm="handleConfirm"
    />
  </AuthenticatedLayout>
</template>
