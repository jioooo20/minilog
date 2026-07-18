<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  incidentId: { type: [Number, String], required: true },
  sourceGroup: { type: String, default: null },
});

const emit = defineEmits(['uploaded']);

// State
const files = ref([]);
const descriptions = ref({});
const isDragging = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);
const errorMessage = ref('');
const confirmDeleteId = ref(null);

const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
const maxFileSize = 10 * 1024 * 1024; // 10MB
const maxFiles = 5;

// File validation
const isValidFile = (file) => {
  const ext = file.name.split('.').pop()?.toLowerCase();
  if (!allowedExtensions.includes(ext)) {
    return { valid: false, message: `"${file.name}" — Tipe file tidak didukung. Gunakan: jpg, jpeg, png, gif, webp, pdf` };
  }
  if (file.size > maxFileSize) {
    return { valid: false, message: `"${file.name}" — Ukuran maksimal 10MB.` };
  }
  return { valid: true, message: '' };
};

// File size formatter
const formatSize = (bytes) => {
  if (!bytes) return '-';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

// Preview URL generator
const getPreviewUrl = (file) => {
  if (file.type?.startsWith('image/')) {
    return URL.createObjectURL(file);
  }
  return null;
};

// Drag & drop handlers
const onDragEnter = (e) => {
  e.preventDefault();
  isDragging.value = true;
};
const onDragLeave = (e) => {
  e.preventDefault();
  isDragging.value = false;
};
const onDrop = (e) => {
  e.preventDefault();
  isDragging.value = false;
  const droppedFiles = Array.from(e.dataTransfer.files);
  addFiles(droppedFiles);
};

// File input handler
const onFileInput = (e) => {
  const selectedFiles = Array.from(e.target.files);
  addFiles(selectedFiles);
  // Reset input so same file can be re-selected
  e.target.value = '';
};

// Add files with validation
const addFiles = (newFiles) => {
  errorMessage.value = '';

  if (files.value.length + newFiles.length > maxFiles) {
    errorMessage.value = `Maksimal ${maxFiles} file dalam satu kali upload.`;
    return;
  }

  const validFiles = [];
  for (const file of newFiles) {
    const validation = isValidFile(file);
    if (!validation.valid) {
      errorMessage.value = validation.message;
      return;
    }
    validFiles.push(file);
  }

  for (const file of validFiles) {
    files.value.push(file);
  }
};

// Remove file from queue
const removeFile = (index) => {
  files.value.splice(index, 1);
  errorMessage.value = '';
};

// Upload all files
const uploadFiles = async () => {
  if (files.value.length === 0) {
    errorMessage.value = 'Pilih minimal satu file.';
    return;
  }

  isUploading.value = true;
  uploadProgress.value = 0;
  errorMessage.value = '';

  const formData = new FormData();
  for (let i = 0; i < files.value.length; i++) {
    formData.append('files[]', files.value[i]);
    formData.append('descriptions[]', descriptions.value[i] || '');
  }
  if (props.sourceGroup) {
    formData.append('source', props.sourceGroup);
  }

  try {
    const response = await axios.post(route('incidents.attachments.store', props.incidentId), formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.total) {
          uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
        }
      },
    });

    uploadProgress.value = 100;
    files.value = [];
    descriptions.value = {};
    emit('uploaded', response.data?.attachments ?? []);
  } catch (error) {
    errorMessage.value = error.response?.data?.message || error.message || 'Upload gagal.';
  } finally {
    isUploading.value = false;
    uploadProgress.value = 0;
  }
};
</script>

<template>
  <div class="space-y-4">
    <!-- Error message -->
    <div
      v-if="errorMessage"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ errorMessage }}
    </div>

    <!-- Drag & drop zone -->
    <div
      class="relative cursor-pointer rounded-xl border-2 border-dashed p-6 text-center transition-colors"
      :class="isDragging ? 'border-slate-900 bg-slate-50' : 'border-slate-300 hover:border-slate-400 hover:bg-slate-50'"
      @dragover.prevent="onDragEnter"
      @dragenter="onDragEnter"
      @dragleave="onDragLeave"
      @drop="onDrop"
      @click="$refs.fileInput?.click()"
    >
      <input
        ref="fileInput"
        type="file"
        multiple
        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
        class="hidden"
        @change="onFileInput"
      />

      <div class="flex flex-col items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <div class="text-sm text-slate-600">
          <span class="font-semibold text-slate-900">Klik untuk upload</span> atau drag & drop file
        </div>
        <div class="text-xs text-slate-500">
          JPEG, PNG, GIF, WebP, PDF — Maks 10MB per file (maks {{ maxFiles }} file)
        </div>
      </div>
    </div>

    <!-- File queue -->
    <div v-if="files.length > 0" class="space-y-3">
      <div class="text-sm font-medium text-slate-700">
        {{ files.length }} file(s) dipilih
      </div>

      <div
        v-for="(file, index) in files"
        :key="index"
        class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white p-3"
      >
        <!-- Preview thumbnail -->
        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
          <img
            v-if="getPreviewUrl(file)"
            :src="getPreviewUrl(file)"
            alt="Preview"
            class="h-full w-full object-cover"
          />
          <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
          </div>
        </div>

        <!-- File info -->
        <div class="min-w-0 flex-1">
          <div class="truncate text-sm font-medium text-slate-900">{{ file.name }}</div>
          <div class="text-xs text-slate-500">{{ formatSize(file.size) }}</div>

          <!-- Description input -->
          <input
            v-model="descriptions[index]"
            type="text"
            placeholder="Deskripsi (optional)"
            maxlength="200"
            class="mt-2 w-full rounded-lg border-slate-200 text-xs focus:border-slate-400 focus:ring-slate-400"
          />
        </div>

        <!-- Remove button -->
        <button
          type="button"
          class="shrink-0 rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
          :disabled="isUploading"
          @click="removeFile(index)"
          aria-label="Remove file"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>

      <!-- Upload button + progress -->
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="isUploading || files.length === 0"
          @click="uploadFiles"
        >
          {{ isUploading ? 'Uploading...' : 'Upload ' + files.length + ' File(s)' }}
        </button>

        <!-- Progress bar -->
        <div v-if="isUploading" class="flex-1">
          <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
            <div
              class="h-full rounded-full bg-slate-900 transition-all duration-300"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>
          <div class="mt-1 text-xs text-slate-500">{{ uploadProgress }}%</div>
        </div>
      </div>
    </div>
  </div>
</template>
