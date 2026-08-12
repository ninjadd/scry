<template>
  <div class="flex-1 flex flex-col overflow-hidden">
    <!-- Header -->
    <div class="p-6 border-b border-slate-800 bg-slate-900/40 flex items-center justify-between">
      <div>
        <div class="flex items-center space-x-3 mb-1">
          <router-link to="/" class="text-xs text-slate-400 hover:text-slate-200">&larr; Back to Tables</router-link>
          <span class="text-xs text-slate-600">/</span>
          <span class="text-xs text-indigo-400 font-mono">{{ table }}</span>
        </div>
        <h2 class="text-2xl font-bold font-mono text-slate-100">{{ table }}</h2>
      </div>

      <div class="flex items-center space-x-3">
        <router-link
          :to="{ name: 'schema', params: { table } }"
          class="px-3 py-1.5 text-xs font-medium rounded-md bg-slate-800 text-slate-300 hover:bg-slate-700 transition-colors"
        >
          View Schema
        </router-link>
        <button
          @click="fetchData"
          class="px-3 py-1.5 text-xs font-medium rounded-md bg-indigo-600/20 text-indigo-300 hover:bg-indigo-600/30 transition-colors"
        >
          Refresh Data
        </button>
      </div>
    </div>

    <!-- Data Table Container -->
    <div class="flex-1 overflow-auto p-6">
      <div v-if="loading" class="py-20 text-center text-slate-500">
        Loading table data...
      </div>

      <div v-else-if="rows.length === 0" class="py-20 text-center text-slate-500">
        No records found in table [{{ table }}].
      </div>

      <div v-else class="border border-slate-800 rounded-xl overflow-hidden shadow-2xl bg-slate-900/50">
        <table class="w-full text-left text-xs font-mono">
          <thead class="bg-slate-900 border-b border-slate-800 text-slate-400 uppercase tracking-wider sticky top-0">
            <tr>
              <th
                v-for="col in columns"
                :key="col"
                @click="sort(col)"
                class="px-4 py-3 cursor-pointer hover:text-slate-200 select-none whitespace-nowrap"
              >
                <div class="flex items-center space-x-1">
                  <span>{{ col }}</span>
                  <span v-if="sortBy === col" class="text-indigo-400">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <tr v-for="(row, i) in rows" :key="i" class="hover:bg-slate-800/40 transition-colors">
              <td
                v-for="col in columns"
                :key="col"
                class="px-4 py-2.5 max-w-xs truncate"
                :title="row[col]"
              >
                <span v-if="row[col] === null" class="text-slate-600 italic">null</span>
                <span v-else-if="typeof row[col] === 'boolean'" class="text-purple-400">{{ row[col] }}</span>
                <span v-else-if="typeof row[col] === 'number'" class="text-amber-400">{{ row[col] }}</span>
                <span v-else>{{ row[col] }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination Footer -->
    <div v-if="meta.total" class="p-4 border-t border-slate-800 bg-slate-900/40 flex items-center justify-between text-xs text-slate-400 font-mono">
      <div>
        Showing page <span class="text-slate-200 font-semibold">{{ meta.page }}</span> of <span class="text-slate-200 font-semibold">{{ meta.last_page }}</span> ({{ meta.total.toLocaleString() }} records total)
      </div>

      <div class="flex space-x-2">
        <button
          :disabled="meta.page <= 1"
          @click="changePage(meta.page - 1)"
          class="px-3 py-1.5 rounded bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-700 text-slate-200"
        >
          Previous
        </button>
        <button
          :disabled="meta.page >= meta.last_page"
          @click="changePage(meta.page + 1)"
          class="px-3 py-1.5 rounded bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-700 text-slate-200"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, inject } from 'vue';

const props = defineProps({ table: String });
const baseApiUrl = inject('baseApiUrl');

const rows = ref([]);
const meta = ref({});
const loading = ref(true);
const page = ref(1);
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
      per_page: 25,
      ...(sortBy.value && { sort_by: sortBy.value, sort_dir: sortDir.value }),
    });

    const res = await fetch(`${baseApiUrl}/tables/${props.table}/data?${params}`);
    const data = await res.json();
    rows.value = data.data || [];
    meta.value = data.meta || {};
  } catch (err) {
    console.error('Failed to load table data:', err);
  } finally {
    loading.value = false;
  }
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
</script>
