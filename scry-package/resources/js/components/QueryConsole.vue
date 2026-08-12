<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6">
    <div class="mb-4">
      <h2 class="text-2xl font-bold text-slate-100 mb-1">SQL Query Console</h2>
      <p class="text-sm text-slate-400">Execute custom read queries against your active database connection.</p>
    </div>

    <!-- Query Editor -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-4 flex flex-col">
      <textarea
        v-model="queryText"
        rows="4"
        placeholder="SELECT * FROM users LIMIT 10;"
        class="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 font-mono text-sm text-indigo-300 placeholder-slate-600 focus:outline-none focus:border-indigo-500"
      ></textarea>
      
      <div class="flex items-center justify-between mt-3">
        <div class="text-xs text-slate-500 font-mono">
          <span v-if="executionTime">Execution time: <strong class="text-emerald-400">{{ executionTime }} ms</strong></span>
        </div>
        <button
          @click="runQuery"
          :disabled="executing || !queryText.trim()"
          class="px-5 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white disabled:opacity-50 transition-colors"
        >
          {{ executing ? 'Running...' : 'Execute Query' }}
        </button>
      </div>
    </div>

    <!-- Results -->
    <div class="flex-1 overflow-auto bg-slate-900/60 border border-slate-800 rounded-xl p-4">
      <div v-if="error" class="p-4 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-mono">
        {{ error }}
      </div>

      <div v-else-if="results.length === 0" class="py-12 text-center text-slate-500 text-xs font-mono">
        No results to display. Enter a query and click Execute Query.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs font-mono">
          <thead class="bg-slate-900 border-b border-slate-800 text-slate-400 uppercase">
            <tr>
              <th v-for="col in columns" :key="col" class="px-4 py-2.5">{{ col }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <tr v-for="(row, i) in results" :key="i" class="hover:bg-slate-800/30">
              <td v-for="col in columns" :key="col" class="px-4 py-2 text-slate-200">
                {{ row[col] }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, inject } from 'vue';

const baseApiUrl = inject('baseApiUrl');

const queryText = ref('SELECT * FROM information_schema.tables WHERE table_schema = \'public\';');
const results = ref([]);
const executionTime = ref(null);
const executing = ref(false);
const error = ref('');

const columns = computed(() => {
  if (results.value.length === 0) return [];
  return Object.keys(results.value[0]);
});

const runQuery = async () => {
  executing.value = true;
  error.value = '';
  results.value = [];
  executionTime.value = null;

  try {
    const res = await fetch(`${baseApiUrl}/query`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ query: queryText.value }),
    });

    if (!res.ok) {
      const errData = await res.json();
      throw new Error(errData.message || 'Query execution failed.');
    }

    const data = await res.json();
    results.value = data.results || [];
    executionTime.value = data.execution_time_ms;
  } catch (err) {
    error.value = err.message;
  } finally {
    executing.value = false;
  }
};
</script>
