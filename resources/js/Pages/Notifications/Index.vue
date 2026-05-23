<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';

const formatDate = (value) => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
};

const notifications = ref([]);
const stats = ref({ unread_count: 0, total_count: 0, today_count: 0 });
const loading = ref(true);

const fetchNotifications = async (page = 1) => {
  loading.value = true;
  try {
    const url = route('notifications.index') + `?page=${page}`;
    const res = await axios.get(url);
    // response format: { data: <resource collection>, stats: {...} }
    notifications.value = (res.data?.data ?? []).map(n => n);
    stats.value = res.data?.stats ?? {};
  } catch (e) {
    // ignore for now
  } finally {
    loading.value = false;
  }
};

const markAsRead = async (notif) => {
  try {
    const url = route('notifications.mark-read', notif.notif_id);
    await axios.post(url);
    // refresh
    fetchNotifications();
    // notify layout to update unread badge
    window.dispatchEvent(new CustomEvent('notifications-updated', { detail: { unread_count: stats.value.unread_count } }));
  } catch (e) {
    // ignore
  }
};

const markAllAsRead = async () => {
  try {
    const url = route('notifications.mark-all-read');
    const res = await axios.post(url);
    // refresh list
    await fetchNotifications();
    // notify layout with updated unread_count
    window.dispatchEvent(new CustomEvent('notifications-updated', { detail: { unread_count: stats.value.unread_count } }));
  } catch (e) {
    // ignore
  }
};

onMounted(() => fetchNotifications());
</script>

<template>

  <Head title="Notifikasi" />

  <AuthenticatedLayout>

    <template #header>
      <div class="flex items-center justify-between gap-3">
        <div>
          <h1 class="text-lg font-semibold text-slate-900">Notification List</h1>
          <p class="text-sm text-slate-500">Notifications that can be accessed by the currently logged-in user.</p>
        </div>
      </div>
    </template>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Notification</h1>
        <div class="text-sm text-slate-500">Unread: <span class="font-semibold">{{ stats.unread_count }}</span></div>
      </div>

      <div class="flex justify-end gap-2">
        <button @click="markAllAsRead" class="rounded bg-rose-600 px-3 py-1 text-xs font-semibold text-white">Mark All
          As
          Read</button>
      </div>

      <div v-if="loading" class="text-sm text-slate-500">Loading...</div>

      <div v-else>
        <ul class="space-y-2">
          <li v-for="n in notifications" :key="n.notif_id" class="rounded-lg border border-slate-200 bg-white p-3">
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1">
                <div class="text-sm text-slate-800">{{ n.message }}</div>
                <div v-if="n.incident?.verification_notes || n.incident?.verified_at || n.incident?.resolved_at"
                  class="mt-2 space-y-1 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                  <div v-if="n.incident?.verified_at"><span class="font-semibold text-slate-700">Verification
                      Date:</span>
                    {{ formatDate(n.incident.verified_at) }}</div>
                  <div v-if="n.incident?.resolved_at"><span class="font-semibold text-slate-700">Repair
                      Completed:</span> {{
                        formatDate(n.incident.resolved_at) }}</div>
                  <div v-if="n.incident?.verification_notes"><span class="font-semibold text-slate-700">Verification
                      Notes:</span> {{ n.incident.verification_notes }}</div>
                </div>
                <div class="mt-1 text-xs text-slate-500">{{ n.created_at }}</div>
                <div v-if="n.incident" class="mt-2 text-xs">
                  <Link :href="route('incidents.show', n.incident.incident_id)" class="text-indigo-600">View Incident:
                    {{
                      n.incident.title }}</Link>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <button v-if="!n.is_read" @click="markAsRead(n)"
                  class="rounded bg-emerald-600 px-3 py-1 text-xs font-semibold text-white">Mark as Read</button>
              </div>
            </div>
          </li>
        </ul>

        <div v-if="notifications.length === 0" class="text-sm text-slate-500">No notifications yet.</div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>
