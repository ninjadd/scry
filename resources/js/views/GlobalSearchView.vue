<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app">
    <!-- Header -->
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 select-none">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Global Cross-Table Database Search</h2>
        <p class="text-sm scry-text-muted">
          Scan text, varchar, UUID, and JSON columns across all tables simultaneously for connection 
          <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.
        </p>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="showTableFilters = !showTableFilters"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer"
          :class="showTableFilters ? 'scry-accent-bg font-bold shadow-sm' : 'scry-badge-glaucous'"
        >
          <span>Table Filters ({{ selectedTables.length ? selectedTables.length : 'All' }})</span>
        </button>
      </div>
    </div>

    <!-- Table Scope Drawer / Filter Accordion -->
    <div v-if="showTableFilters" class="mb-4 p-4 rounded-xl border scry-border scry-bg-card shadow-sm space-y-3">
      <div class="flex items-center justify-between">
        <h4 class="text-xs font-bold uppercase tracking-wider scry-text-subtle">Target Search Scope</h4>
        <div class="space-x-2 text-xs font-mono">
          <button @click="selectAllTables" class="scry-accent-text hover:underline cursor-pointer">All Tables</button>
          <span class="text-slate-400">|</span>
          <button @click="selectedTables = []" class="scry-text-muted hover:underline cursor-pointer">Reset</button>
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2 max-h-36 overflow-y-auto p-2 rounded-lg border scry-border scry-bg-input">
        <label
          v-for="t in availableTables"
          :key="t.name"
          class="flex items-center space-x-1.5 text-xs font-mono scry-text-main cursor-pointer p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-800 truncate"
        >
          <input
            type="checkbox"
            :value="t.name"
            v-model="selectedTables"
            class="rounded text-pink-600 focus:ring-pink-500"
          />
          <span :title="t.name" class="truncate">{{ t.name }}</span>
        </label>
      </div>
    </div>

    <!-- Search Input & Options Bar -->
    <div class="scry-bg-card border scry-border rounded-xl p-4 mb-4 shadow-sm space-y-3">
      <div class="flex flex-col sm:flex-row items-center gap-3">
        <div class="relative flex-1 w-full">
          <input
            v-model="searchTerm"
            @keyup.enter="performSearch"
            type="text"
            placeholder="Enter global search keyword (e.g. John, admin@domain.com, uuid, active)..."
            class="w-full scry-bg-input border scry-border rounded-lg px-4 py-2.5 text-sm scry-text-main focus:outline-none focus:ring-2 focus:ring-pink-500 font-mono shadow-inner"
          />
        </div>

        <div class="flex items-center space-x-2 w-full sm:w-auto">
          <select
            v-model.number="perTableLimit"
            class="scry-bg-input border scry-border rounded-lg px-3 py-2 text-xs font-mono scry-text-main focus:outline-none"
            title="Max sample matches per table"
          >
            <option :value="5">5 per table</option>
            <option :value="10">10 per table</option>
            <option :value="25">25 per table</option>
            <option :value="50">50 per table</option>
          </select>

          <button
            @click="performSearch"
            :disabled="searching || !searchTerm.trim()"
            class="px-6 py-2.5 text-xs font-semibold rounded-lg scry-accent-bg disabled:opacity-50 transition-colors cursor-pointer shadow-sm flex items-center space-x-1.5"
          >
            <span>{{ searching ? 'Searching...' : 'Search All Tables' }}</span>
          </button>
        </div>
      </div>

      <!-- Search Meta Status -->
      <div v-if="searched && !searching" class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t scry-border text-xs font-mono">
        <div class="flex items-center space-x-2">
          <span class="px-2.5 py-0.5 rounded font-bold scry-badge-glaucous">
            {{ totalMatches }} Total Matches Found
          </span>
          <span class="scry-text-muted">across <strong class="scry-accent-text">{{ results.length }}</strong> tables</span>
        </div>

        <div class="flex items-center space-x-2 scry-text-muted">
          <span>Searched {{ totalSearchedTables }} tables in <strong class="scry-accent-text">{{ executionTimeMs }}ms</strong></span>
        </div>
      </div>
    </div>

    <!-- Results Area -->
    <div class="flex-1 overflow-y-auto space-y-4 pr-1">
      <!-- Searching Spinner -->
      <div v-if="searching" class="py-24 text-center scry-text-muted font-mono text-xs">
        <div class="inline-block animate-spin mb-3 text-2xl">⏳</div>
        <p>Scanning all text and JSON columns across active database connection...</p>
      </div>

      <!-- No Results State -->
      <div v-else-if="searched && results.length === 0" class="py-16 text-center scry-text-muted font-mono text-xs scry-bg-card border scry-border rounded-xl p-8">
        No matching records found for keyword "<strong class="scry-accent-text">{{ searchedTerm }}</strong>" in connection [{{ store.currentConnection }}].
      </div>

      <!-- Results List Grouped by Table -->
      <div
        v-for="res in results"
        :key="res.table"
        class="scry-bg-card border scry-border rounded-xl p-4 shadow-sm space-y-3"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b scry-border pb-2.5">
          <div class="flex items-center space-x-2">
            <span class="font-mono font-bold text-base scry-accent-text">{{ res.table }}</span>
            <span class="text-xs px-2.5 py-0.5 rounded font-mono font-bold scry-badge-glaucous">
              {{ res.match_count }} match{{ res.match_count === 1 ? '' : 'es' }}
            </span>
            <span class="text-[11px] scry-text-subtle font-mono hidden md:inline">
              (Total rows in table: {{ res.total_rows_in_table.toLocaleString() }})
            </span>
          </div>

          <div class="flex items-center space-x-2">
            <router-link
              :to="{ name: 'data', params: { table: res.table } }"
              class="px-3 py-1.5 text-xs font-semibold rounded scry-badge-pale-blue hover:opacity-80 transition-opacity font-mono"
            >
              Browse Full Table &rarr;
            </router-link>
          </div>
        </div>

        <!-- Matched Columns List -->
        <div class="text-[11px] font-mono scry-text-muted flex items-center space-x-1.5 flex-wrap">
          <span class="text-slate-400">Searched Columns:</span>
          <span
            v-for="col in res.matched_columns"
            :key="col"
            class="px-1.5 py-0.2 rounded bg-slate-500/10 text-slate-400 font-medium"
          >
            {{ col }}
          </span>
        </div>

        <!-- Sample Records Table -->
        <div class="overflow-x-auto rounded-lg border scry-border">
          <table class="w-full text-left text-xs font-mono select-none">
            <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
              <tr>
                <th
                  v-for="col in Object.keys(res.sample_matches[0] || {})"
                  :key="col"
                  class="px-3.5 py-2 whitespace-nowrap"
                  :class="res.matched_columns.includes(col) ? 'scry-accent-text font-bold' : ''"
                >
                  {{ col }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y scry-border-subtle scry-text-main">
              <tr
                v-for="(row, i) in res.sample_matches"
                :key="i"
                class="hover:bg-slate-50 dark:hover:bg-slate-800/40"
              >
                <td
                  v-for="(val, col) in row"
                  :key="col"
                  class="px-3.5 py-2 max-w-xs truncate"
                  :title="String(val)"
                >
                  <span v-if="val === null" class="text-slate-400 italic">NULL</span>
                  <span
                    v-else-if="shouldHighlight(val)"
                    class="scry-accent-text font-bold bg-pink-500/15 px-1.5 py-0.5 rounded"
                  >
                    {{ val }}
                  </span>
                  <span v-else>{{ val }}</span>
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
import { ref, onMounted, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';
import { useToastStore } from '../stores/useToastStore';

const store = useConnectionStore();
const toast = useToastStore();

const availableTables = ref([]);
const selectedTables = ref([]);
const showTableFilters = ref(false);

const searchTerm = ref('');
const searchedTerm = ref('');
const perTableLimit = ref(10);

const searching = ref(false);
const searched = ref(false);
const results = ref([]);
const totalMatches = ref(0);
const totalSearchedTables = ref(0);
const executionTimeMs = ref(null);

const loadTables = async () => {
  try {
    const res = await store.scryFetch('/tables');
    if (res.ok) {
      const data = await res.json();
      availableTables.value = data.tables || [];
    }
  } catch (err) {
    console.error(err);
  }
};

const selectAllTables = () => {
  selectedTables.value = availableTables.value.map(t => t.name);
};

const performSearch = async () => {
  if (!searchTerm.value.trim()) return;

  searching.value = true;
  searched.value = true;
  searchedTerm.value = searchTerm.value.trim();
  results.value = [];
  totalMatches.value = 0;

  try {
    const res = await store.scryFetch('/search/global', {
      method: 'POST',
      body: JSON.stringify({
        term: searchedTerm.value,
        limit: perTableLimit.value,
        tables: selectedTables.value.length ? selectedTables.value : [],
      }),
    });

    const data = await res.json();
    if (!res.ok || data.error) {
      throw new Error(data.error || 'Global search failed.');
    }

    results.value = data.results || [];
    totalMatches.value = data.total_matches || 0;
    totalSearchedTables.value = data.total_tables_searched || 0;
    executionTimeMs.value = data.execution_time_ms || 0;
  } catch (err) {
    toast.error(err.message);
  } finally {
    searching.value = false;
  }
};

const shouldHighlight = (val) => {
  if (!searchedTerm.value || typeof val !== 'string') return false;
  return val.toLowerCase().includes(searchedTerm.value.toLowerCase());
};

onMounted(loadTables);
watch(() => store.currentConnection, () => {
  loadTables();
  if (searched.value && searchTerm.value.trim()) {
    performSearch();
  }
});
</script>
