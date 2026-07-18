<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import ImageLightbox from '@/Components/ImageLightbox.vue';

const props = defineProps({
  attachments: { type: Array, default: () => [] },
  incidentId: { type: [Number, String], required: true },
  canDelete: { type: Boolean, default: false },
});

const emit = defineEmits(['deleted']);

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);
const confirmDeleteId = ref(null);

// Filter image attachments
const imageAttachments = computed(() =>
  props.attachments.filter((a) => a.mime_type?.startsWith('image/'))
);

// Filter non-image attachments (PDF, etc)
const documentAttachments = computed(() =>
  props.attachments.filter((a) => !a.mime_type?.startsWith('image/'))
);

// Generate lightbox images array
const lightboxImages = computed(() =>
  imageAttachments.value.map((a) => ({
    url: getAttachmentUrl(a.file_path),
    file_name: a.file_name,
    description: a.description,
  }))
);

// Get full URL for attachment file
const getAttachmentUrl = (filePath) => {
  if (!filePath) return '';
  // relative path from storage
  return '/storage/' + filePath;
};

// Format file size
const formatSize = (bytes) => {
  if (!bytes) return '-';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

// Format date
const formatDate = (value) => {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(d);
};

// Open lightbox for image at index
const openLightbox = (index) => {
  lightboxIndex.value = index;
  lightboxOpen.value = true;
};

// Download attachment
const downloadFile = async (attachment) => {
  try {
    const url = route('incidents.attachments.download', [props.incidentId, attachment.attachment_id]);
    window.open(url, '_blank');
  } catch (e) {
    console.error('Download error', e);
  }
};

// Delete attachment
const confirmDelete = (attachmentId) => {
  confirmDeleteId.value = attachmentId;
};

const cancelDelete = () => {
  confirmDeleteId.value = null;
};

const executeDelete = async () => {
  if (!confirmDeleteId.value) return;
  try {
    await axios.delete(route('incidents.attachments.destroy', [props.incidentId, confirmDeleteId.value]));
    confirmDeleteId.value = null;
    emit('deleted', confirmDeleteId.value);
  } catch (error) {
    console.error('Delete attachment error', error);
    alert(error.response?.data?.message || 'Gagal menghapus file.');
  }
};

// Source stage label mapping
const sourceLabels = {
  create: { label: 'Create Incident', class: 'bg-slate-600 text-white' },
  investigation: { label: 'Investigasi', class: 'bg-amber-500 text-white' },
  repair: { label: 'Repair', class: 'bg-blue-600 text-white' },
  verification: { label: 'Verifikasi', class: 'bg-emerald-600 text-white' },
  closing: { label: 'Closing', class: 'bg-purple-600 text-white' },
};
const getSourceInfo = (source) => {
  if (!source) return null;
  return sourceLabels[source] || { label: source, class: 'bg-slate-500 text-white' };
};

// Get icon for mime type
const getFileIcon = (mimeType) => {
  if (!mimeType) return 'default';
  if (mimeType.includes('pdf')) return 'pdf';
  if (mimeType.includes('image')) return 'image';
  return 'default';
};
</script>

<template>
  <div v-if="attachments.length > 0" class="space-y-6">
    <!-- Image gallery -->
    <div v-if="imageAttachments.length > 0">
      <h4 class="mb-3 text-sm font-semibold text-slate-700">Images ({{ imageAttachments.length }})</h4>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
        <div
          v-for="(attachment, index) in imageAttachments"
          :key="attachment.attachment_id"
          class="group relative cursor-pointer overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm transition hover:shadow-md"
          @click="openLightbox(index)"
        >
          <!-- Thumbnail -->
          <div class="aspect-square overflow-hidden">
            <img
              :src="getAttachmentUrl(attachment.file_path)"
              :alt="attachment.file_name"
              class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
              loading="lazy"
            />
          </div>

          <!-- Source badge -->
          <div
            v-if="getSourceInfo(attachment.source)"
            class="absolute left-1.5 top-1.5 rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase leading-tight shadow-sm"
            :class="getSourceInfo(attachment.source).class"
          >
            {{ getSourceInfo(attachment.source).label }}
          </div>

          <!-- Overlay on hover -->
          <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 transition group-hover:opacity-100">
            <div class="w-full p-2 text-xs text-white truncate">
              {{ attachment.file_name }}
            </div>
          </div>

          <!-- Delete button (top-right corner) -->
          <button
            v-if="canDelete"
            type="button"
            class="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-black/40 text-white opacity-0 transition hover:bg-rose-600 group-hover:opacity-100"
            @click.stop="confirmDelete(attachment.attachment_id)"
            aria-label="Delete"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Document list (PDF, etc.) -->
    <div v-if="documentAttachments.length > 0">
      <h4 class="mb-3 text-sm font-semibold text-slate-700">Dokumen ({{ documentAttachments.length }})</h4>
      <div class="space-y-2">
        <div
          v-for="attachment in documentAttachments"
          :key="attachment.attachment_id"
          class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3 transition hover:shadow-sm"
        >
          <!-- Icon -->
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
            <!-- PDF icon -->
            <svg v-if="getFileIcon(attachment.mime_type) === 'pdf'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <!-- Default file icon -->
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>

          <!-- File info -->
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <span class="truncate text-sm font-medium text-slate-900">{{ attachment.file_name }}</span>
              <span
                v-if="getSourceInfo(attachment.source)"
                class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase leading-tight"
                :class="getSourceInfo(attachment.source).class"
              >
                {{ getSourceInfo(attachment.source).label }}
              </span>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-500">
              <span>{{ formatSize(attachment.file_size) }}</span>
              <span v-if="attachment.uploaded_by?.name">&middot; oleh {{ attachment.uploaded_by.name }}</span>
              <span v-if="attachment.uploaded_at">&middot; {{ formatDate(attachment.uploaded_at) }}</span>
            </div>
            <div v-if="attachment.description" class="mt-0.5 text-xs text-slate-600">{{ attachment.description }}</div>
          </div>

          <!-- Actions -->
          <div class="flex shrink-0 items-center gap-1">
            <!-- Download button -->
            <button
              type="button"
              class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
              @click="downloadFile(attachment)"
              aria-label="Download"
              title="Download"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </button>

            <!-- Delete button -->
            <button
              v-if="canDelete"
              type="button"
              class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
              @click="confirmDelete(attachment.attachment_id)"
              aria-label="Delete"
              title="Delete"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Empty state -->
  <div
    v-else
    class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
    <p class="mt-2 text-sm text-slate-500">Belum ada attachment.</p>
  </div>

  <!-- Delete confirmation modal -->
  <Teleport to="body">
    <div
      v-if="confirmDeleteId"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40"
      @click.self="cancelDelete"
    >
      <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">Hapus File?</h3>
        <p class="mt-2 text-sm text-slate-600">File yang dihapus tidak bisa dikembalikan. Lanjutkan?</p>
        <div class="mt-6 flex justify-end gap-3">
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            @click="cancelDelete"
          >
            Batal
          </button>
          <button
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="executeDelete"
          >
            Ya, Hapus
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Image Lightbox -->
  <ImageLightbox
    :show="lightboxOpen"
    :images="lightboxImages"
    :initial-index="lightboxIndex"
    @close="lightboxOpen = false"
  />
</template>
