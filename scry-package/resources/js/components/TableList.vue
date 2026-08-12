<template>
  <div class="flex-1 p-6 overflow-y-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-100">Database Tables</h2>
        <p class="text-sm text-slate-400">Inspecting database structure and storage statistics.</p>
      </div>

      <div class="relative w-64">
        <input
          v-model="search"
          type="text"
          placeholder="Filter tables..."
          class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-20 text-slate-500">
      <svg class="animate-spin h-6 w-6 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>
      Loading tables...
    </div>

    <!-- Table Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="table in filteredTables"
        :key="table.name"
        class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-5 hover:border-indigo-500/50 transition-all group"
      >
        <div class="flex items-start justify-between mb-3">
          <h3 class="font-mono text-base font-semibold text-slate-200 group-hover:text-indigo-400 transition-colors">
            {{ table.name }}
          </h3>
          <span class="text-xs font-mono text-slate-400 bg-slate-800 px-2 py-0.5 rounded">
            {{ table.size }}
          </span>
        </div>

        <div class="text-xs text-slate-400 mb-4">
          Estimated rows: <span class="font-mono text-slate-200">{{ table.rows.toLocaleString() }}</span>
        </div>

        <div class="flex items-center space-x-2 pt-3 border-t border-slate-800/60">
          <router-link
            :to="{ name: 'data', params: { table: table.name } }"
            class="flex-1 text-center py-1.5 text-xs font-medium rounded-md bg-indigo-600/20 text-indigo-300 hover:bg-indigo-600/30 transition-colors"
          >
            View Data
          </router-link>
          <router-link
            :to="{ name: 'schema', params: { table: table.name } }"
            class="flex-1 text-center py-1.5 text-xs font-medium rounded-md bg-slate-800 text-slate-300 hover:bg-slate-700 transition-colors"
          >
            View Schema
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue';

const emit = defineEmits(['update-driver', 'tables-loaded']);
const baseApiUrl = inject('baseApiUrl');

const tables = ref([]);
const loading = ref(true);
const search = ref('');

const filteredTables = computed(() => {
  if (!search.value) return tables.value;
  return tables.value.filter(t => t.name.toLowerCase().includes(search.value.toLowerCase()));
});

onMounted(async () => {
  try {
    const res = await fetch(`${baseApiUrl}/tables`);
    const data = await res.json();
    tables.value = data.tables || [];
    emit('update-driver', data.driver);
    emit('tables-loaded', data.tables);
  } catch (err) {
    console.error('Failed to fetch tables:', err);
  } finally {
    loading.value = false;
  }
});
</script>
