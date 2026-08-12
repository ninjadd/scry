<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app">
    <div class="mb-4">
      <h2 class="text-2xl font-bold scry-text-main mb-1">SQL Query Console</h2>
      <p class="text-sm scry-text-muted">Execute custom read queries against connection <span class="font-mono scry-accent-text font-bold">[{{ connection }}]</span>.</p>
    </div>

    <!-- Query Editor -->
    <div class="scry-bg-card border scry-border rounded-xl p-4 mb-4 flex flex-col shadow-sm">
      <textarea
        v-model="queryText"
        rows="4"
        placeholder="SELECT * FROM users LIMIT 10;"
        class="w-full scry-bg-input border scry-border rounded-lg p-3 font-mono text-sm scry-accent-text placeholder:scry-text-subtle focus:outline-none focus:border-pink-600 shadow-inner"
      ></textarea>
      
      <div class="flex items-center justify-between mt-3">
        <div class="text-xs scry-text-muted font-mono">
          <span v-if="executionTime">Execution time: <strong class="text-emerald-600 dark:text-emerald-400 font-bold">{{ executionTime }} ms</strong></span>
        </div>
        <button
          @click="runQuery"
          :disabled="executing || !queryText.trim()"
          class="px-5 py-2 text-xs font-semibold rounded-lg scry-accent-bg disabled:opacity-50 transition-colors shadow-sm cursor-pointer"
        >
          {{ executing ? 'Running...' : 'Execute Query' }}
        </button>
      </div>
    </div>

    <!-- Results -->
    <div class="flex-1 overflow-auto scry-bg-card border scry-border rounded-xl p-4 shadow-sm">
      <div v-if="error" class="p-4 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-mono">
        {{ error }}
      </div>

      <div v-else-if="results.length === 0" class="py-12 text-center scry-text-muted text-xs font-mono">
        No results to display. Enter a query and click Execute Query.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs font-mono">
          <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
            <tr>
              <th v-for="col in columns" :key="col" class="px-4 py-2.5">{{ col }}</th>
            </tr>
          </thead>
          <tbody class="divide-y scry-border-subtle scry-text-main">
            <tr v-for="(row, i) in results" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <td v-for="col in columns" :key="col" class="px-4 py-2 scry-text-main">
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

const props = defineProps({ connection: String, isDark: Boolean });
const baseApiUrl = inject('baseApiUrl');

const queryText = ref('SELECT * FROM users LIMIT 10;');
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
      body: JSON.stringify({ query: queryText.value, connection: props.connection }),
    });

    if (!res.ok) {
      const errData = await res.json();
      throw new Error(errData.error || errData.message || 'Query execution failed.');
    }

    const data = await res.json();
    results.value = data.data || [];
    executionTime.value = data.execution_time_ms;
  } catch (err) {
    error.value = err.message;
  } finally {
    executing.value = false;
  }
};
</script>
