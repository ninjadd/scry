<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Raw SQL Execution Console</h2>
        <p class="text-sm scry-text-muted">Run raw queries, batch commands, and DDL queries directly on connection <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="showBookmarksDrawer = !showBookmarksDrawer"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-badge-pale-blue transition-colors cursor-pointer"
        >
          Bookmarks ({{ bookmarks.length }})
        </button>
        <button
          @click="saveCurrentBookmark"
          :disabled="!query.trim()"
          class="px-3 py-2 text-xs font-semibold rounded-lg scry-badge-sulphur transition-colors disabled:opacity-50 cursor-pointer"
        >
          Bookmark Query
        </button>
      </div>
    </div>

    <!-- Main Workspace Area -->
    <div class="flex-1 flex space-x-4 overflow-hidden">
      <!-- Editor & Results Section -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Editor Input Card -->
        <div class="scry-bg-card border scry-border rounded-xl p-4 mb-4 shadow-sm flex flex-col">
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold uppercase tracking-wider scry-text-subtle">SQL Command Editor</label>
            <div class="flex space-x-2 text-[11px]">
              <button @click="query = 'SELECT * FROM users LIMIT 10;'" class="scry-accent-text hover:underline cursor-pointer">SELECT Users</button>
              <span class="scry-text-subtle">&bull;</span>
              <button @click="query = 'SHOW TABLES;'" class="scry-accent-text hover:underline cursor-pointer">SHOW Tables</button>
            </div>
          </div>

          <textarea
            v-model="query"
            rows="5"
            placeholder="Type raw SQL query here (e.g. SELECT * FROM users;)..."
            class="w-full scry-bg-input border scry-border rounded-lg p-3 text-xs font-mono scry-accent-text focus:outline-none focus:border-pink-600 shadow-inner"
          ></textarea>

          <div class="mt-3 flex items-center justify-between">
            <div class="text-xs font-mono scry-text-muted">
              <span v-if="executionTimeMs !== null">Execution time: <strong class="scry-accent-text">{{ executionTimeMs }} ms</strong></span>
            </div>

            <div class="flex space-x-2">
              <button
                @click="query = ''"
                class="px-3 py-1.5 text-xs font-semibold rounded-lg border scry-border scry-text-main"
              >
                Clear
              </button>
              <button
                @click="runQuery"
                :disabled="executing || !query.trim()"
                class="px-5 py-1.5 text-xs font-semibold rounded-lg scry-accent-bg disabled:opacity-50 transition-colors cursor-pointer shadow-sm"
              >
                {{ executing ? 'Executing...' : 'Run Query' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Output & Results Card -->
        <div class="flex-1 overflow-auto scry-bg-card border scry-border rounded-xl p-4 shadow-sm">
          <div v-if="error" class="p-4 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-mono">
            <strong>Execution Error:</strong> {{ error }}
          </div>

          <div v-else-if="affectedRows !== null" class="p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-mono font-bold">
            Query executed successfully. Affected rows: {{ affectedRows }}
          </div>

          <div v-else-if="results.length > 0" class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
              <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
                <tr>
                  <th v-for="col in columns" :key="col" class="px-4 py-2.5">{{ col }}</th>
                </tr>
              </thead>
              <tbody class="divide-y scry-border-subtle scry-text-main">
                <tr v-for="(row, i) in results" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                  <td v-for="col in columns" :key="col" class="px-4 py-2 scry-text-main max-w-xs truncate" :title="row[col]">
                    {{ row[col] }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="py-16 text-center text-xs scry-text-muted font-mono">
            Execute a query above to display output results.
          </div>
        </div>
      </div>

      <!-- Bookmarks Drawer Sidebar -->
      <div v-if="showBookmarksDrawer" class="w-80 scry-bg-card border scry-border rounded-xl p-4 shadow-sm flex flex-col justify-between overflow-y-auto">
        <div>
          <div class="flex items-center justify-between border-b scry-border pb-2 mb-3">
            <h3 class="font-bold text-xs uppercase tracking-wider scry-text-main">SQL Bookmarks</h3>
            <button @click="showBookmarksDrawer = false" class="text-xs scry-text-muted font-bold">&times;</button>
          </div>

          <div v-if="bookmarks.length === 0" class="text-xs text-center scry-text-muted py-6 font-mono">
            No saved query bookmarks yet.
          </div>

          <div class="space-y-2">
            <div
              v-for="(b, idx) in bookmarks"
              :key="idx"
              class="p-2.5 rounded-lg border scry-border scry-bg-input text-xs font-mono space-y-1.5"
            >
              <div class="flex items-center justify-between font-bold scry-accent-text">
                <span>{{ b.title }}</span>
                <button @click="removeBookmark(idx)" class="text-rose-500 hover:underline text-[10px]">&times;</button>
              </div>
              <p class="scry-text-muted truncate text-[11px]">{{ b.sql }}</p>
              <button @click="query = b.sql" class="w-full text-center px-2 py-1 rounded scry-badge-glaucous text-[10px] font-semibold cursor-pointer">
                Load Query
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useConnectionStore } from '../stores/useConnectionStore';

const store = useConnectionStore();
const route = useRoute();

const query = ref('');
const executing = ref(false);
const error = ref('');
const results = ref([]);
const columns = ref([]);
const affectedRows = ref(null);
const executionTimeMs = ref(null);

const showBookmarksDrawer = ref(false);
const bookmarks = ref(JSON.parse(localStorage.getItem('scry-sql-bookmarks') || '[]'));

const runQuery = async () => {
  if (!query.value.trim()) return;

  executing.value = true;
  error.value = '';
  results.value = [];
  columns.value = [];
  affectedRows.value = null;
  executionTimeMs.value = null;

  try {
    const res = await store.scryFetch('/sql/execute', {
      method: 'POST',
      body: JSON.stringify({ query: query.value }),
    });

    const data = await res.json();
    if (!res.ok || data.error) {
      throw new Error(data.error || 'Execution failed');
    }

    executionTimeMs.value = data.execution_time_ms || 0;

    if (data.type === 'SELECT' || data.type === 'EXPLAIN' || data.data) {
      results.value = data.data || [];
      columns.value = data.columns || (results.value.length > 0 ? Object.keys(results.value[0]) : []);
    } else {
      affectedRows.value = data.affected_rows ?? 0;
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    executing.value = false;
  }
};

const saveCurrentBookmark = () => {
  const title = prompt('Enter title for this SQL bookmark:', 'Saved Query');
  if (!title) return;

  bookmarks.value.push({ title, sql: query.value });
  localStorage.setItem('scry-sql-bookmarks', JSON.stringify(bookmarks.value));
  showBookmarksDrawer.value = true;
};

const removeBookmark = (index) => {
  bookmarks.value.splice(index, 1);
  localStorage.setItem('scry-sql-bookmarks', JSON.stringify(bookmarks.value));
};

onMounted(() => {
  if (route.query.sql) {
    query.value = route.query.sql;
  }
});
</script>
