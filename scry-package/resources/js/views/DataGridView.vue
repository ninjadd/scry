<template>
  <div class="flex-1 flex flex-col overflow-hidden scry-bg-app">
    <!-- Header -->
    <div class="p-6 border-b scry-border scry-bg-header flex items-center justify-between">
      <div>
        <div class="flex items-center space-x-3 mb-1">
          <router-link to="/tables" class="text-xs scry-text-muted hover:scry-text-main">&larr; Back to Tables</router-link>
          <span class="text-xs scry-text-subtle">/</span>
          <span class="text-xs scry-accent-text font-mono font-bold">{{ table }}</span>
        </div>
        <h2 class="text-2xl font-bold font-mono scry-text-main">{{ table }}</h2>
      </div>

      <div class="flex items-center space-x-3">
        <a
          :href="`${store.baseApiUrl}/export/${table}?format=csv&connection=${store.currentConnection}`"
          target="_blank"
          download
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-glaucous hover:opacity-80 transition-opacity shadow-sm flex items-center space-x-1"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <span>Export CSV</span>
        </a>
        <a
          :href="`${store.baseApiUrl}/export/${table}?format=sql&connection=${store.currentConnection}`"
          target="_blank"
          download
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-sulphur hover:opacity-80 transition-opacity shadow-sm flex items-center space-x-1"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
          </svg>
          <span>Export SQL</span>
        </a>
        <router-link
          :to="{ name: 'schema', params: { table } }"
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-pale-blue transition-colors shadow-sm"
        >
          View Schema
        </router-link>
        <button
          @click="fetchData"
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-accent-bg transition-colors shadow-sm cursor-pointer"
        >
          Refresh Data
        </button>
      </div>
    </div>

    <!-- Data Table Container -->
    <div class="flex-1 overflow-auto p-6">
      <div v-if="loading" class="py-20 text-center scry-text-muted">
        Loading table data...
      </div>

      <div v-else-if="rows.length === 0" class="py-20 text-center scry-text-muted">
        No records found in table [{{ table }}].
      </div>

      <div v-else class="border scry-border rounded-xl overflow-hidden shadow-lg scry-bg-card">
        <table class="w-full text-left text-xs font-mono">
          <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider sticky top-0">
            <tr>
              <th
                v-for="col in columns"
                :key="col"
                @click="sort(col)"
                class="px-4 py-3 cursor-pointer hover:scry-text-main select-none whitespace-nowrap"
              >
                <div class="flex items-center space-x-1">
                  <span>{{ col }}</span>
                  <span v-if="sortBy === col" class="scry-accent-text font-bold">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y scry-border-subtle scry-text-main">
            <tr v-for="(row, i) in rows" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
              <td
                v-for="col in columns"
                :key="col"
                class="px-4 py-2.5 max-w-xs truncate"
                :title="row[col]"
              >
                <span v-if="row[col] === null" class="scry-text-subtle italic">null</span>
                <span v-else-if="typeof row[col] === 'boolean'" class="px-1.5 py-0.5 rounded text-[10px] font-bold scry-badge-glaucous">{{ row[col] }}</span>
                <span v-else-if="typeof row[col] === 'number'" class="px-1.5 py-0.5 rounded text-[10px] font-bold scry-badge-sulphur">{{ row[col] }}</span>
                <span v-else>{{ row[col] }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination Footer -->
    <div v-if="meta.total" class="p-4 border-t scry-border scry-bg-header flex flex-col sm:flex-row items-center justify-between gap-3 text-xs scry-text-muted font-mono">
      <div class="flex items-center space-x-3">
        <div>
          Showing page <span class="scry-text-main font-bold">{{ meta.page }}</span> of <span class="scry-text-main font-bold">{{ meta.last_page }}</span> ({{ meta.total.toLocaleString() }} records total)
        </div>
        <div class="flex items-center space-x-1.5 border-l scry-border pl-3">
          <span class="text-[11px] scry-text-subtle">Per page:</span>
          <select
            v-model="perPage"
            @change="handlePerPageChange"
            class="scry-bg-input border scry-border rounded px-2 py-1 text-xs scry-text-main focus:outline-none focus:ring-1 focus:ring-pink-500/50"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>

      <div class="flex space-x-2">
        <button
          :disabled="meta.page <= 1"
          @click="changePage(meta.page - 1)"
          class="px-3 py-1.5 rounded scry-bg-card border scry-border disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-800 scry-text-main font-medium cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500/50"
        >
          Previous
        </button>
        <button
          :disabled="meta.page >= meta.last_page"
          @click="changePage(meta.page + 1)"
          class="px-3 py-1.5 rounded scry-bg-card border scry-border disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-800 scry-text-main font-medium cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500/50"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';

const props = defineProps({ table: String });
const store = useConnectionStore();

const rows = ref([]);
const meta = ref({});
const loading = ref(true);
const page = ref(1);
const perPage = ref(25);
const sortBy = ref(null);
const sortDir = ref('asc');

const columns = computed(() => {
  if (rows.value.length === 0) return [];
  return Object.keys(rows.value[0]);
});

const fetchData = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      page: page.value,
      per_page: perPage.value,
      ...(sortBy.value && { sort_by: sortBy.value, sort_dir: sortDir.value }),
    });

    const res = await store.scryFetch(`/tables/${props.table}/rows?${params}`);
    if (res.ok) {
      const data = await res.json();
      rows.value = data.data || [];
      meta.value = data.meta || {};
    }
  } catch (err) {
    console.error('Failed to load table data:', err);
  } finally {
    loading.value = false;
  }
};

const handlePerPageChange = () => {
  page.value = 1;
  fetchData();
};

const changePage = (newPage) => {
  page.value = newPage;
  fetchData();
};

const sort = (col) => {
  if (sortBy.value === col) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = col;
    sortDir.value = 'asc';
  }
  fetchData();
};

onMounted(fetchData);
watch(() => props.table, () => {
  page.value = 1;
  sortBy.value = null;
  fetchData();
});
watch(() => store.currentConnection, () => {
  page.value = 1;
  fetchData();
});
</script>
