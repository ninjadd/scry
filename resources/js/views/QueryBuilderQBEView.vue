<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Query-by-Example (QBE) Visual Builder</h2>
        <p class="text-sm scry-text-muted">Visually compose complex queries with joins, filters, and aggregations for <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="openInConsole"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-badge-pale-blue transition-colors cursor-pointer"
        >
          Edit in SQL Console
        </button>
        <button
          @click="executeDirectly"
          :disabled="executing"
          class="px-4 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors cursor-pointer disabled:opacity-50"
        >
          {{ executing ? 'Running...' : 'Execute Directly' }}
        </button>
      </div>
    </div>

    <!-- Builder Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
      <!-- Select Primary Table & Fields -->
      <div class="scry-bg-card border scry-border rounded-xl p-4 shadow-sm space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider scry-text-subtle">1. Select Main Table & Columns</h3>
        <select
          v-model="selectedTable"
          @change="loadTableColumns"
          class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main focus:outline-none"
        >
          <option value="">-- Choose Table --</option>
          <option v-for="t in availableTables" :key="t.name" :value="t.name">{{ t.name }}</option>
        </select>

        <div v-if="columns.length > 0">
          <label class="block text-xs font-semibold scry-text-muted mb-2">Select Columns (* for all)</label>
          <div class="max-h-36 overflow-y-auto space-y-1 p-2 border scry-border rounded scry-bg-input">
            <label class="flex items-center space-x-2 text-xs font-mono scry-text-main cursor-pointer">
              <input type="checkbox" v-model="selectAllCols" @change="toggleAllCols" />
              <span class="font-bold">* (All Columns)</span>
            </label>
            <label v-for="col in columns" :key="col.name" class="flex items-center space-x-2 text-xs font-mono scry-text-main cursor-pointer">
              <input type="checkbox" :value="col.name" v-model="selectedColumns" />
              <span>{{ col.name }}</span>
            </label>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Aggregate Function (Optional)</label>
          <div class="flex space-x-2">
            <select v-model="aggregateFunc" class="w-1/2 scry-bg-input border scry-border rounded p-1.5 text-xs font-mono scry-text-main">
              <option value="">None</option>
              <option value="COUNT">COUNT()</option>
              <option value="SUM">SUM()</option>
              <option value="AVG">AVG()</option>
              <option value="MIN">MIN()</option>
              <option value="MAX">MAX()</option>
            </select>
            <select v-model="aggregateCol" :disabled="!aggregateFunc || aggregateFunc === 'COUNT'" class="w-1/2 scry-bg-input border scry-border rounded p-1.5 text-xs font-mono scry-text-main disabled:opacity-50">
              <option value="*">*</option>
              <option v-for="col in columns" :key="col.name" :value="col.name">{{ col.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Joins & Conditions -->
      <div class="scry-bg-card border scry-border rounded-xl p-4 shadow-sm space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider scry-text-subtle">2. Joins & Filter Conditions</h3>

        <!-- Multi-table JOIN -->
        <div class="p-2.5 border scry-border rounded-lg scry-bg-input space-y-2 text-xs font-mono">
          <span class="font-bold scry-accent-text">Table JOIN (Optional)</span>
          <div class="grid grid-cols-2 gap-2">
            <select v-model="joinType" class="scry-bg-input border scry-border rounded p-1 text-xs">
              <option value="INNER JOIN">INNER JOIN</option>
              <option value="LEFT JOIN">LEFT JOIN</option>
              <option value="RIGHT JOIN">RIGHT JOIN</option>
            </select>
            <select v-model="joinTable" class="scry-bg-input border scry-border rounded p-1 text-xs">
              <option value="">-- Target Table --</option>
              <option v-for="t in availableTables.filter(t => t.name !== selectedTable)" :key="t.name" :value="t.name">{{ t.name }}</option>
            </select>
          </div>
          <div v-if="joinTable" class="flex items-center space-x-1">
            <span class="text-[10px] scry-text-muted">ON</span>
            <input v-model="joinLocalCol" placeholder="main_col" class="w-1/2 border scry-border rounded p-1 text-xs scry-bg-input" />
            <span class="text-[10px] scry-text-muted">=</span>
            <input v-model="joinForeignCol" placeholder="foreign_col" class="w-1/2 border scry-border rounded p-1 text-xs scry-bg-input" />
          </div>
        </div>

        <!-- Filter -->
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Filter WHERE Condition</label>
          <div class="flex space-x-2">
            <select v-model="filterCol" class="w-1/3 scry-bg-input border scry-border rounded p-1.5 text-xs font-mono scry-text-main">
              <option value="">-- No Filter --</option>
              <option v-for="col in columns" :key="col.name" :value="col.name">{{ col.name }}</option>
            </select>
            <select v-if="filterCol" v-model="filterOp" class="w-1/4 scry-bg-input border scry-border rounded p-1.5 text-xs font-mono scry-text-main">
              <option value="=">=</option>
              <option value="LIKE">LIKE</option>
              <option value=">">&gt;</option>
              <option value="<">&lt;</option>
            </select>
            <input v-if="filterCol" v-model="filterVal" type="text" placeholder="Value..." class="w-5/12 scry-bg-input border scry-border rounded p-1.5 text-xs font-mono scry-text-main" />
          </div>
        </div>

        <!-- GROUP BY & ORDER BY -->
        <div class="grid grid-cols-2 gap-2 text-xs font-mono">
          <div>
            <label class="block font-semibold scry-text-muted mb-1">GROUP BY</label>
            <select v-model="groupByCol" class="w-full scry-bg-input border scry-border rounded p-1 text-xs">
              <option value="">-- None --</option>
              <option v-for="col in columns" :key="col.name" :value="col.name">{{ col.name }}</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold scry-text-muted mb-1">ORDER BY</label>
            <div class="flex space-x-1">
              <select v-model="sortCol" class="w-2/3 scry-bg-input border scry-border rounded p-1 text-xs">
                <option value="">-- None --</option>
                <option v-for="col in columns" :key="col.name" :value="col.name">{{ col.name }}</option>
              </select>
              <select v-if="sortCol" v-model="sortDir" class="w-1/3 scry-bg-input border scry-border rounded p-1 text-xs">
                <option value="ASC">ASC</option>
                <option value="DESC">DESC</option>
              </select>
            </div>
          </div>
        </div>

        <div class="flex items-center space-x-2 pt-1">
          <span class="text-xs scry-text-muted">Limit:</span>
          <input v-model.number="limit" type="number" min="1" max="1000" class="w-20 scry-bg-input border scry-border rounded p-1 text-xs font-mono scry-text-main" />
        </div>
      </div>

      <!-- Generated SQL Preview -->
      <div class="scry-bg-card border scry-border rounded-xl p-4 shadow-sm flex flex-col justify-between">
        <div>
          <h3 class="text-xs font-bold uppercase tracking-wider scry-text-subtle mb-3">3. Live SQL Query Preview</h3>
          <div class="p-3 rounded-lg scry-bg-input border scry-border font-mono text-xs scry-accent-text whitespace-pre-wrap leading-relaxed">
            {{ generatedSql }}
          </div>
        </div>
      </div>
    </div>

    <!-- Direct Results Display -->
    <div v-if="results.length > 0 || queryError" class="flex-1 overflow-auto scry-bg-card border scry-border rounded-xl p-4 shadow-sm">
      <div v-if="queryError" class="p-4 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-mono">
        {{ queryError }}
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs font-mono">
          <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
            <tr>
              <th v-for="col in resultCols" :key="col" class="px-4 py-2.5">{{ col }}</th>
            </tr>
          </thead>
          <tbody class="divide-y scry-border-subtle scry-text-main">
            <tr v-for="(row, i) in results" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <td v-for="col in resultCols" :key="col" class="px-4 py-2 scry-text-main max-w-xs truncate" :title="row[col]">
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
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useConnectionStore } from '../stores/useConnectionStore';

const store = useConnectionStore();
const router = useRouter();

const availableTables = ref([]);
const selectedTable = ref('');
const columns = ref([]);
const selectedColumns = ref([]);
const selectAllCols = ref(true);

const aggregateFunc = ref('');
const aggregateCol = ref('*');

const joinType = ref('INNER JOIN');
const joinTable = ref('');
const joinLocalCol = ref('');
const joinForeignCol = ref('');

const filterCol = ref('');
const filterOp = ref('=');
const filterVal = ref('');

const groupByCol = ref('');
const sortCol = ref('');
const sortDir = ref('ASC');
const limit = ref(25);

const executing = ref(false);
const queryError = ref('');
const results = ref([]);
const resultCols = ref([]);

const loadTables = async () => {
  try {
    const res = await store.scryFetch('/tables');
    if (res.ok) {
      const data = await res.json();
      availableTables.value = data.tables || [];
      if (availableTables.value.length > 0) {
        selectedTable.value = availableTables.value[0].name;
        loadTableColumns();
      }
    }
  } catch (err) {
    console.error(err);
  }
};

const loadTableColumns = async () => {
  if (!selectedTable.value) return;
  try {
    const res = await store.scryFetch(`/tables/${selectedTable.value}/schema`);
    if (res.ok) {
      const data = await res.json();
      columns.value = data.columns || [];
      selectedColumns.value = columns.value.map(c => c.name);
      selectAllCols.value = true;
    }
  } catch (err) {
    console.error(err);
  }
};

const toggleAllCols = () => {
  if (selectAllCols.value) {
    selectedColumns.value = columns.value.map(c => c.name);
  } else {
    selectedColumns.value = [];
  }
};

const generatedSql = computed(() => {
  if (!selectedTable.value) return '-- Select a table to generate query';

  let selectClause = '*';
  if (aggregateFunc.value) {
    const aggTarget = aggregateFunc.value === 'COUNT' ? '*' : (aggregateCol.value || '*');
    selectClause = `${aggregateFunc.value}(${aggTarget}) AS total_result`;
    if (groupByCol.value) {
      selectClause = `${groupByCol.value}, ${selectClause}`;
    }
  } else if (!selectAllCols.value && selectedColumns.value.length > 0) {
    selectClause = selectedColumns.value.map(c => `${selectedTable.value}.${c}`).join(', ');
  }

  let sql = `SELECT ${selectClause} FROM ${selectedTable.value}`;

  if (joinTable.value && joinLocalCol.value && joinForeignCol.value) {
    sql += ` ${joinType.value} ${joinTable.value} ON ${selectedTable.value}.${joinLocalCol.value} = ${joinTable.value}.${joinForeignCol.value}`;
  }

  if (filterCol.value && filterVal.value) {
    const valEscaped = filterOp.value === 'LIKE' ? `'%${filterVal.value}%'` : `'${filterVal.value}'`;
    sql += ` WHERE ${selectedTable.value}.${filterCol.value} ${filterOp.value} ${valEscaped}`;
  }

  if (groupByCol.value) {
    sql += ` GROUP BY ${selectedTable.value}.${groupByCol.value}`;
  }

  if (sortCol.value) {
    sql += ` ORDER BY ${selectedTable.value}.${sortCol.value} ${sortDir.value}`;
  }

  if (limit.value) {
    sql += ` LIMIT ${limit.value}`;
  }

  return sql + ';';
});

const openInConsole = () => {
  router.push({ name: 'query', query: { sql: generatedSql.value } });
};

const executeDirectly = async () => {
  executing.value = true;
  queryError.value = '';
  results.value = [];

  try {
    const res = await store.scryFetch('/sql/execute', {
      method: 'POST',
      body: JSON.stringify({ query: generatedSql.value }),
    });
    const data = await res.json();

    if (!res.ok || data.error) {
      throw new Error(data.error || 'Execution failed');
    }

    results.value = data.data || [];
    resultCols.value = data.columns || (results.value.length > 0 ? Object.keys(results.value[0]) : []);
  } catch (err) {
    queryError.value = err.message;
  } finally {
    executing.value = false;
  }
};

onMounted(loadTables);
watch(() => store.currentConnection, loadTables);
</script>
