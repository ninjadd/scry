<template>
  <div class="inline-flex items-center space-x-2 font-mono text-xs">
    <!-- Image Preview Mode -->
    <template v-if="isImage">
      <img :src="imageDataUrl" class="w-8 h-8 object-cover rounded border scry-border shadow-sm cursor-pointer" @click="showModal = true" title="Click to expand BLOB image" />
      <span class="text-[10px] scry-text-subtle">Image BLOB</span>
    </template>

    <!-- Generic Binary / Hex Mode -->
    <template v-else>
      <span class="px-2 py-0.5 rounded text-[10px] font-bold scry-badge-pale-blue">BLOB ({{ byteSize }} bytes)</span>
      <button @click="downloadBinary" class="text-xs scry-accent-text hover:underline cursor-pointer">
        Download Binary
      </button>
    </template>

    <!-- Expanded Image Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showModal = false">
      <div class="scry-bg-card border scry-border rounded-xl p-4 max-w-lg w-full shadow-2xl">
        <div class="flex items-center justify-between mb-3 border-b scry-border-subtle pb-2">
          <h3 class="font-bold text-sm scry-text-main">BLOB Image Preview</h3>
          <button @click="showModal = false" class="text-xs scry-text-muted hover:scry-text-main font-bold">Close &times;</button>
        </div>
        <img :src="imageDataUrl" class="w-full h-auto max-h-96 object-contain rounded border scry-border" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  value: [String, Object, Array],
  mimeType: { type: String, default: 'image/png' },
});

const showModal = ref(false);

const isImage = computed(() => {
  if (typeof props.value !== 'string') return false;
  return props.value.startsWith('data:image/') || props.value.startsWith('iVBORw0KGgo') || props.value.startsWith('/9j/');
});

const imageDataUrl = computed(() => {
  if (props.value?.startsWith('data:image/')) return props.value;
  return `data:${props.mimeType};base64,${props.value}`;
});

const byteSize = computed(() => {
  if (typeof props.value === 'string') return props.value.length;
  return 0;
});

const downloadBinary = () => {
  const blob = new Blob([props.value], { type: 'application/octet-stream' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'blob_data.bin';
  a.click();
};
</script>
