<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app">
    <div class="mb-4">
      <h2 class="text-2xl font-bold scry-text-main mb-1">Global Database Search</h2>
      <p class="text-sm scry-text-muted">Search string patterns across all text columns and tables in connection <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
    </div>

    <!-- Search Form -->
    <div class="scry-bg-card border scry-border rounded-xl p-4 mb-4 flex items-center space-x-3 shadow-sm">
      <input
        v-model="searchTerm"
        @keyup.enter="performSearch"
        type="text"
        placeholder="Enter search term (e.g. admin, tech, @example.com)..."
        class="flex-1 scry-bg-input border scry-border rounded-lg px-3 py-2 text-sm scry-text-main focus:outline-none focus:border-pink-600 shadow-inner font-mono"
      />
      <button
        @click="performSearch"
        :disabled="searching || !searchTerm.trim()"
        class="px-5 py-2 text-xs font-semibold rounded-lg scry-accent-bg disabled:opacity-50 transition-colors cursor-pointer shadow-sm"
      >
        {{ searching ? 'Searching...' : 'Search Database' }}
      </button>
    </div>

    <!-- Results Area -->
    <div class="flex-1 overflow-auto space-y-4">
      <div v-if="searching" class="py-20 text-center scry-text-muted">
        Scanning tables for "{{ searchTerm }}"...
      </div>

      <div v-else-if="searched && results.length === 0" class="py-12 text-center scry-text-muted font-mono text-xs">
        No matching records found for term "{{ searchTerm }}".
      </div>

      <div
        v-for="res in results"
        :key="res.table"
        class="scry-bg-card border scry-border rounded-xl p-4 shadow-sm"
      >
        <div class="flex items-center justify-between mb-3 border-b scry-border-subtle pb-2">
          <div class="flex items-center space-x-2">
            <h3 class="font-mono font-bold text-sm scry-accent-text">{{ res.table }}</h3>
            <span class="text-xs scry-text-muted font-mono">({{ res.match_count }} match records found)</span>
          </div>

          <router-link
            :to="{ name: 'data', params: { table: res.table } }"
            class="px-3 py-1 text-xs font-semibold rounded scry-badge-glaucous transition-colors"
          >
            Open Table Data &rarr;
          </router-link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs font-mono">
            <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase">
              <tr>
                <th v-for="col in Object.keys(res.sample_matches[0] || {})" :key="col" class="px-3 py-2">{{ col }}</th>
              </tr>
            </thead>
            <tbody class="divide-y scry-border-subtle scry-text-main">
              <tr v-for="(m, i) in res.sample_matches" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <td v-for="(v, k) in m" :key="k" class="px-3 py-1.5 max-w-xs truncate">
                  <span v-if="typeof v === 'string' && v.toLowerCase().includes(searchTerm.toLowerCase())" class="scry-accent-text font-bold bg-pink-500/10 px-1 rounded">
                    {{ v }}
                  </span>
                  <span v-else>{{ v }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';

const store = useConnectionStore();
const searchTerm = ref('');
const searching = ref(false);
const searched = ref(false);
const results = ref([]);

const performSearch = async () => {
  if (!searchTerm.value.trim()) return;
  searching.value = true;
  searched.value = true;
  results.value = [];

  try {
    const res = await store.scryFetch(`/search?q=${encodeURIComponent(searchTerm.value)}`);
    if (res.ok) {
      const data = await res.json();
      results.value = data.results || [];
    }
  } catch (err) {
    console.error(err);
  } finally {
    searching.value = false;
  }
};
</script>
