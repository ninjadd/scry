<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 select-none font-mono">
    <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-2xl w-full shadow-2xl space-y-4">
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b scry-border pb-3">
        <div class="flex items-center space-x-2">
          <span class="text-xs uppercase font-bold tracking-wider scry-accent-text">Index Manager</span>
          <span class="text-xs px-2 py-0.5 rounded scry-badge-glaucous font-bold">{{ tableName }}</span>
        </div>
        <button @click="$emit('close')" class="text-xs scry-text-muted font-bold cursor-pointer hover:scry-text-main">
          ✕ Close
        </button>
      </div>

      <!-- Main Body -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h4 class="text-xs font-bold uppercase tracking-wider scry-text-subtle">Existing Table Indexes</h4>
          <button
            @click="showCreateForm = !showCreateForm"
            class="px-2.5 py-1 text-xs font-bold rounded scry-accent-bg transition-colors cursor-pointer"
          >
            {{ showCreateForm ? 'Cancel New Index' : '+ Create New Index' }}
          </button>
        </div>

        <!-- Create Index Form -->
        <div v-if="showCreateForm" class="p-4 border scry-border rounded-lg scry-bg-input space-y-3">
          <h5 class="text-xs font-bold scry-accent-text">Create New Index</h5>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">Index Name</label>
              <input
                v-model="newIndexName"
                type="text"
                placeholder="idx_column_name"
                class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main"
              />
            </div>
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">Column Name</label>
              <input
                v-model="newIndexColumn"
                type="text"
                placeholder="e.g. title, slug"
                class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main"
              />
            </div>
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">Index Type</label>
              <select
                v-model="newIndexType"
                class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main"
              >
                <option value="INDEX">INDEX (Non-Unique)</option>
                <option value="UNIQUE">UNIQUE</option>
                <option value="FULLTEXT">FULLTEXT</option>
              </select>
            </div>
          </div>

          <div class="flex justify-end pt-1">
            <button
              @click="executeCreateIndex"
              :disabled="!newIndexName.trim() || !newIndexColumn.trim() || isSubmitting"
              class="px-4 py-1.5 text-xs font-bold rounded scry-accent-bg disabled:opacity-50 transition-colors cursor-pointer shadow-sm"
            >
              {{ isSubmitting ? 'Creating...' : 'Execute CREATE INDEX' }}
            </button>
          </div>
        </div>

        <!-- Indexes Table -->
        <div v-if="loading" class="py-12 text-center text-xs scry-text-muted">
          Loading index metadata...
        </div>

        <div v-else-if="indexes.length === 0" class="py-8 text-center text-xs scry-text-muted bg-slate-500/5 rounded-lg border scry-border">
          No secondary indexes found on table [{{ tableName }}].
        </div>

        <div v-else class="border scry-border rounded-lg overflow-hidden">
          <table class="w-full text-left text-xs font-mono">
            <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
              <tr>
                <th class="px-4 py-2.5">Index Name</th>
                <th class="px-4 py-2.5">Column(s)</th>
                <th class="px-4 py-2.5">Type</th>
                <th class="px-4 py-2.5 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y scry-border-subtle scry-text-main">
              <tr v-for="idx in indexes" :key="idx.name" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <td class="px-4 py-2.5 font-bold scry-accent-text">{{ idx.name }}</td>
                <td class="px-4 py-2.5">{{ idx.columns ? idx.columns.join(', ') : idx.column }}</td>
                <td class="px-4 py-2.5">
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-bold scry-badge-glaucous">
                    {{ idx.type || 'INDEX' }}
                  </span>
                </td>
                <td class="px-4 py-2.5 text-right">
                  <button
                    @click="executeDropIndex(idx.name)"
                    class="px-2 py-1 text-[10px] font-bold rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 hover:bg-rose-500/25 transition-colors cursor-pointer"
                  >
                    Drop Index
                  </button>
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
import { ref, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';

const props = defineProps({
  show: Boolean,
  tableName: String,
});

const emit = defineEmits(['close']);
const store = useConnectionStore();

const loading = ref(false);
const showCreateForm = ref(false);
const isSubmitting = ref(false);
const indexes = ref([]);

const newIndexName = ref('');
const newIndexColumn = ref('');
const newIndexType = ref('INDEX');

const fetchIndexes = async () => {
  if (!props.tableName) return;
  loading.value = true;
  try {
    const res = await store.scryFetch(`/tables/${props.tableName}/schema`);
    if (res.ok) {
      const data = await res.json();
      indexes.value = data.indexes || [];
    }
  } catch (err) {
    console.error('Failed to fetch indexes:', err);
  } finally {
    loading.value = false;
  }
};

const executeCreateIndex = async () => {
  if (!newIndexName.value.trim() || !newIndexColumn.value.trim()) return;
  isSubmitting.value = true;

  const openQuote = store.driver === 'sqlsrv' ? '[' : (store.driver === 'pgsql' || store.driver === 'sqlite' ? '"' : '`');
  const closeQuote = store.driver === 'sqlsrv' ? ']' : (store.driver === 'pgsql' || store.driver === 'sqlite' ? '"' : '`');
  const typeStr = newIndexType.value === 'UNIQUE' ? 'UNIQUE INDEX' : (newIndexType.value === 'FULLTEXT' ? 'FULLTEXT INDEX' : 'INDEX');
  const sql = `CREATE ${typeStr} ${openQuote}${newIndexName.value}${closeQuote} ON ${openQuote}${props.tableName}${closeQuote} (${openQuote}${newIndexColumn.value}${closeQuote});`;

  try {
    const res = await store.scryFetch('/sql/execute', {
      method: 'POST',
      body: JSON.stringify({ query: sql }),
    });

    if (res.ok) {
      newIndexName.value = '';
      newIndexColumn.value = '';
      showCreateForm.value = false;
      await fetchIndexes();
    } else {
      const data = await res.json();
      alert('Create Index Error: ' + (data.error || 'Execution failed'));
    }
  } catch (err) {
    alert('Create Index Error: ' + err.message);
  } finally {
    isSubmitting.value = false;
  }
};

const executeDropIndex = async (indexName) => {
  if (!confirm(`Are you sure you want to DROP INDEX [${indexName}] from table [${props.tableName}]?`)) return;

  const openQuote = store.driver === 'sqlsrv' ? '[' : (store.driver === 'pgsql' || store.driver === 'sqlite' ? '"' : '`');
  const closeQuote = store.driver === 'sqlsrv' ? ']' : (store.driver === 'pgsql' || store.driver === 'sqlite' ? '"' : '`');

  const sql = (store.driver === 'pgsql' || store.driver === 'sqlite')
    ? `DROP INDEX ${openQuote}${indexName}${closeQuote};`
    : `DROP INDEX ${openQuote}${indexName}${closeQuote} ON ${openQuote}${props.tableName}${closeQuote};`;

  try {
    const res = await store.scryFetch('/sql/execute', {
      method: 'POST',
      body: JSON.stringify({ query: sql }),
    });

    if (res.ok) {
      await fetchIndexes();
    } else {
      const data = await res.json();
      alert('Drop Index Error: ' + (data.error || 'Execution failed'));
    }
  } catch (err) {
    alert('Drop Index Error: ' + err.message);
  }
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    fetchIndexes();
  }
});
</script>
