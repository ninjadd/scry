<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold scry-text-main">Server Performance & Maintenance Advisor</h2>
        <p class="text-sm scry-text-muted">Configuration recommendations and status variables for <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <button
        @click="loadTuning"
        class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm cursor-pointer"
      >
        Re-analyze Server
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-20 text-center scry-text-muted">
      Analyzing server variables...
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="(item, i) in suggestions"
        :key="i"
        class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm"
      >
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center space-x-2">
            <span class="text-xs font-mono font-bold uppercase px-2 py-0.5 rounded scry-badge-pale-blue">
              {{ item.category }}
            </span>
            <h3 class="font-semibold text-base scry-text-main">{{ item.title }}</h3>
          </div>
          <span
            class="text-[10px] font-bold uppercase px-2 py-0.5 rounded"
            :class="item.severity === 'warning' ? 'scry-badge-sulphur' : (item.severity === 'success' ? 'scry-badge-glaucous' : 'scry-badge-pale-blue')"
          >
            {{ item.severity }}
          </span>
        </div>

        <p class="text-xs scry-text-muted leading-relaxed font-mono mt-1">
          {{ item.recommendation }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';

const store = useConnectionStore();
const loading = ref(true);
const suggestions = ref([]);

const loadTuning = async () => {
  loading.value = true;
  try {
    const res = await store.scryFetch('/server/tuning');
    if (res.ok) {
      const data = await res.json();
      suggestions.value = data.suggestions || [];
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

onMounted(loadTuning);
watch(() => store.currentConnection, loadTuning);
</script>
