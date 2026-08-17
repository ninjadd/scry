<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app">
    <!-- Header -->
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 select-none">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Visual Query-by-Example (QBE) Builder</h2>
        <p class="text-sm scry-text-muted">
          Visually construct multi-table SELECT queries, joins, aggregates, and filter criteria for connection 
          <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.
        </p>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="openInConsole"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-badge-pale-blue transition-colors cursor-pointer"
        >
          Edit in SQL Console &rarr;
        </button>
        <button
          @click="executeGeneratedSql"
          :disabled="executing || !generatedSql.trim()"
          class="px-5 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors cursor-pointer disabled:opacity-50 flex items-center space-x-1.5 shadow-sm"
        >
          <span>{{ executing ? 'Executing...' : 'Execute Query' }}</span>
        </button>
      </div>
    </div>

    <!-- Main Visual Builder Area -->
    <div class="flex-1 flex flex-col lg:flex-row gap-4 overflow-hidden">
      <!-- Left Config Controls Column -->
      <div class="lg:w-7/12 flex flex-col space-y-4 overflow-y-auto pr-1">
        <!-- 1. Primary Table & Columns -->
        <div class="scry-bg-card border scry-border rounded-xl p-4 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider scry-text-subtle">
              1. Base Table & Columns
            </h3>
            <span v-if="activeTable" class="text-[10px] font-mono px-2 py-0.5 rounded font-bold scry-badge-glaucous">
              {{ availableColumns.length }} Columns Available
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold scry-text-muted mb-1">Select Primary Table</label>
              <select
                v-model="activeTable"
                @change="onPrimaryTableChange"
                class="w-full scry-bg-input border scry-border rounded-lg p-2 text-xs font-mono scry-text-main focus:outline-none focus:ring-1 focus:ring-pink-500"
              >
                <option value="">-- Choose Base Table --</option>
                <option v-for="t in availableTables" :key="t.name" :value="t.name">{{ t.name }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold scry-text-muted mb-1">Distinct Rows</label>
              <label class="flex items-center space-x-2 mt-2 text-xs font-mono scry-text-main cursor-pointer">
                <input type="checkbox" v-model="isDistinct" class="rounded text-pink-600 focus:ring-pink-500" />
                <span>SELECT DISTINCT</span>
              </label>
            </div>
          </div>

          <!-- Column Checklist -->
          <div v-if="activeTable && availableColumns.length > 0" class="pt-2 border-t scry-border">
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-semibold scry-text-muted">Selected Columns</label>
              <div class="space-x-2 text-[11px]">
                <button @click="selectAllColumns" class="scry-accent-text hover:underline cursor-pointer">Select All</button>
                <span class="text-slate-400">|</span>
                <button @click="deselectAllColumns" class="scry-text-muted hover:underline cursor-pointer">Clear All</button>
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 max-h-36 overflow-y-auto p-2 rounded-lg border scry-border scry-bg-input">
              <label
                v-for="col in availableColumns"
                :key="col.name"
                class="flex items-center space-x-1.5 text-xs font-mono scry-text-main cursor-pointer truncate p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-800"
              >
                <input
                  type="checkbox"
                  :value="col.fullName"
                  v-model="selectedColumns"
                  class="rounded text-pink-600 focus:ring-pink-500"
                />
                <span :title="col.fullName" class="truncate">{{ col.name }}</span>
                <span v-if="col.isPrimary" class="text-[9px] font-bold scry-badge-glaucous px-1 rounded">PK</span>
              </label>
            </div>
          </div>
        </div>

        <!-- 2. Dynamic Multi-Table JOINs -->
        <div class="scry-bg-card border scry-border rounded-xl p-4 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider scry-text-subtle">
              2. Dynamic Table JOIN Relationships
            </h3>
            <button
              @click="addJoinRow"
              class="px-2.5 py-1 text-xs font-semibold rounded scry-accent-bg transition-colors cursor-pointer shadow-sm"
            >
              + Add JOIN Table
            </button>
          </div>

          <div v-if="joins.length === 0" class="text-xs scry-text-muted font-mono py-2 text-center">
            No table joins defined. Click "+ Add JOIN Table" to relate additional tables.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(j, idx) in joins"
              :key="idx"
              class="p-3 rounded-lg border scry-border scry-bg-input space-y-2 text-xs font-mono"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold scry-accent-text">JOIN #{{ idx + 1 }}</span>
                <button @click="removeJoinRow(idx)" class="text-rose-500 hover:underline font-bold text-xs cursor-pointer">
                  &times; Remove
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                  <label class="block text-[11px] scry-text-muted mb-0.5">Join Type</label>
                  <select v-model="j.type" class="w-full scry-bg-card border scry-border rounded p-1.5 text-xs font-mono scry-text-main">
                    <option value="INNER JOIN">INNER JOIN</option>
                    <option value="LEFT JOIN">LEFT JOIN</option>
                    <option value="RIGHT JOIN">RIGHT JOIN</option>
                    <option value="FULL OUTER JOIN">FULL OUTER JOIN</option>
                  </select>
                </div>

                <div>
                  <label class="block text-[11px] scry-text-muted mb-0.5">Target Table</label>
                  <select
                    v-model="j.table"
                    @change="onJoinTableChange(j)"
                    class="w-full scry-bg-card border scry-border rounded p-1.5 text-xs font-mono scry-text-main"
                  >
                    <option value="">-- Select Target Table --</option>
                    <option v-for="t in availableTables.filter(t => t.name !== activeTable)" :key="t.name" :value="t.name">
                      {{ t.name }}
                    </option>
                  </select>
                </div>
              </div>

              <div v-if="j.table" class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t scry-border">
                <div>
                  <label class="block text-[11px] scry-text-muted mb-0.5">Local Key ({{ activeTable }})</label>
                  <input
                    v-model="j.localCol"
                    placeholder="e.g. id, user_id"
                    class="w-full scry-bg-card border scry-border rounded p-1.5 text-xs font-mono scry-text-main"
                  />
                </div>
                <div>
                  <label class="block text-[11px] scry-text-muted mb-0.5">Foreign Key ({{ j.table }})</label>
                  <input
                    v-model="j.foreignCol"
                    placeholder="e.g. id, post_id"
                    class="w-full scry-bg-card border scry-border rounded p-1.5 text-xs font-mono scry-text-main"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. WHERE Filter Criteria -->
        <div class="scry-bg-card border scry-border rounded-xl p-4 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider scry-text-subtle">
              3. Filter Conditions (WHERE)
            </h3>
            <button
              @click="addWhereRow"
              class="px-2.5 py-1 text-xs font-semibold rounded scry-accent-bg transition-colors cursor-pointer shadow-sm"
            >
              + Add Condition
            </button>
          </div>

          <div v-if="whereClauses.length === 0" class="text-xs scry-text-muted font-mono py-2 text-center">
            No filter conditions applied. All rows will be queried.
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="(w, idx) in whereClauses"
              :key="idx"
              class="flex flex-wrap sm:flex-nowrap items-center gap-2 p-2 rounded-lg border scry-border scry-bg-input text-xs font-mono"
            >
              <select
                v-if="idx > 0"
                v-model="w.logical"
                class="w-16 scry-bg-card border scry-border rounded p-1.5 text-xs font-bold scry-accent-text"
              >
                <option value="AND">AND</option>
                <option value="OR">OR</option>
              </select>

              <input
                v-model="w.column"
                placeholder="Column name"
                class="flex-1 min-w-[120px] scry-bg-card border scry-border rounded p-1.5 text-xs font-mono scry-text-main"
              />

              <select v-model="w.operator" class="w-28 scry-bg-card border scry-border rounded p-1.5 text-xs font-mono scry-text-main">
                <option value="=">=</option>
                <option value="!=">&ne; (!=)</option>
                <option value=">">&gt;</option>
                <option value="<">&lt;</option>
                <option value=">=">&ge; (&gt;=)</option>
                <option value="<=">&le; (&lt;=)</option>
                <option value="LIKE">LIKE %..%</option>
                <option value="NOT LIKE">NOT LIKE</option>
                <option value="IN">IN (...)</option>
                <option value="NOT IN">NOT IN (...)</option>
                <option value="IS NULL">IS NULL</option>
                <option value="IS NOT NULL">IS NOT NULL</option>
              </select>

              <input
                v-if="!w.operator.includes('NULL')"
                v-model="w.value"
                placeholder="Value..."
                class="flex-1 min-w-[120px] scry-bg-card border scry-border rounded p-1.5 text-xs font-mono scry-text-main"
              />

              <button @click="removeWhereRow(idx)" class="text-rose-500 font-bold text-base px-1.5 cursor-pointer">&times;</button>
            </div>
          </div>
        </div>

        <!-- 4. Aggregations, GROUP BY & Sorting -->
        <div class="scry-bg-card border scry-border rounded-xl p-4 shadow-sm space-y-3">
          <h3 class="text-xs font-bold uppercase tracking-wider scry-text-subtle">
            4. Aggregates, Grouping & Pagination
          </h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs font-mono">
            <!-- Aggregate Function -->
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">Aggregate Function</label>
              <div class="flex space-x-1.5">
                <select v-model="aggregateFunc" class="w-1/2 scry-bg-input border scry-border rounded p-1.5 text-xs scry-text-main">
                  <option value="">None</option>
                  <option value="COUNT">COUNT()</option>
                  <option value="SUM">SUM()</option>
                  <option value="AVG">AVG()</option>
                  <option value="MIN">MIN()</option>
                  <option value="MAX">MAX()</option>
                </select>
                <input
                  v-model="aggregateCol"
                  :disabled="!aggregateFunc"
                  placeholder="Column or *"
                  class="w-1/2 scry-bg-input border scry-border rounded p-1.5 text-xs scry-text-main disabled:opacity-50"
                />
              </div>
            </div>

            <!-- Group By -->
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">GROUP BY Column</label>
              <input
                v-model="groupByCol"
                placeholder="e.g. category_id, status"
                class="w-full scry-bg-input border scry-border rounded p-1.5 text-xs scry-text-main"
              />
            </div>

            <!-- Order By -->
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">ORDER BY</label>
              <div class="flex space-x-1.5">
                <input
                  v-model="orderByCol"
                  placeholder="Column name"
                  class="w-2/3 scry-bg-input border scry-border rounded p-1.5 text-xs scry-text-main"
                />
                <select v-model="orderDirection" class="w-1/3 scry-bg-input border scry-border rounded p-1.5 text-xs scry-text-main">
                  <option value="ASC">ASC</option>
                  <option value="DESC">DESC</option>
                </select>
              </div>
            </div>

            <!-- Limit / Offset -->
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">LIMIT & OFFSET</label>
              <div class="flex space-x-1.5">
                <input
                  v-model.number="limitCount"
                  type="number"
                  placeholder="Limit (25)"
                  class="w-1/2 scry-bg-input border scry-border rounded p-1.5 text-xs scry-text-main"
                />
                <input
                  v-model.number="offsetCount"
                  type="number"
                  placeholder="Offset (0)"
                  class="w-1/2 scry-bg-input border scry-border rounded p-1.5 text-xs scry-text-main"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Generated SQL & Results Column -->
      <div class="lg:w-5/12 flex flex-col space-y-4 overflow-hidden">
        <!-- Live SQL Preview Card -->
        <div class="scry-bg-card border scry-border rounded-xl p-4 shadow-sm flex flex-col">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-xs font-bold uppercase tracking-wider scry-text-subtle">
              Live Generated SQL
            </h3>
            <button
              @click="copyGeneratedSql"
              class="px-2 py-1 text-[11px] font-semibold rounded scry-badge-pale-blue hover:opacity-80 transition-opacity cursor-pointer font-mono"
            >
              Copy SQL
            </button>
          </div>

          <div class="p-3 rounded-lg scry-bg-input border scry-border font-mono text-xs scry-accent-text overflow-x-auto min-h-[120px] max-h-[180px]">
            <pre class="whitespace-pre-wrap">{{ generatedSql || '-- Select a base table to generate SQL query...' }}</pre>
          </div>
        </div>

        <!-- Query Execution Results Card -->
        <div class="flex-1 overflow-hidden scry-bg-card border scry-border rounded-xl p-4 shadow-sm flex flex-col">
          <div class="flex items-center justify-between mb-2 border-b scry-border pb-2">
            <div class="flex items-center space-x-2">
              <span class="text-xs font-bold uppercase tracking-wider scry-text-subtle">Execution Output</span>
              <span v-if="executionTimeMs !== null" class="text-xs font-mono scry-text-muted">
                ({{ executionTimeMs }} ms)
              </span>
            </div>

            <div class="flex items-center space-x-1.5" v-if="results.length > 0">
              <span class="text-[11px] font-mono scry-text-muted">{{ results.length }} rows</span>
            </div>
          </div>

          <!-- Error Alert -->
          <div v-if="error" class="p-3 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-mono">
            <strong>Error:</strong> {{ error }}
          </div>

          <!-- Results Grid -->
          <div v-else-if="results.length > 0" class="flex-1 overflow-auto">
            <table class="w-full text-left text-xs font-mono">
              <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase sticky top-0 z-10">
                <tr>
                  <th v-for="col in columns" :key="col" class="px-3 py-2">{{ col }}</th>
                </tr>
              </thead>
              <tbody class="divide-y scry-border-subtle scry-text-main">
                <tr v-for="(r, i) in results" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                  <td v-for="col in columns" :key="col" class="px-3 py-1.5 max-w-xs truncate" :title="String(r[col])">
                    {{ r[col] }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty Output State -->
          <div v-else class="flex-1 flex items-center justify-center text-center text-xs scry-text-muted font-mono">
            Click "Execute Query" to inspect preview records.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useConnectionStore } from '../stores/useConnectionStore';
import { useToastStore } from '../stores/useToastStore';

const router = useRouter();
const store = useConnectionStore();
const toast = useToastStore();

const availableTables = ref([]);
const activeTable = ref('');
const isDistinct = ref(false);
const availableColumns = ref([]);
const selectedColumns = ref([]);

const joins = ref([]);
const whereClauses = ref([]);

const aggregateFunc = ref('');
const aggregateCol = ref('*');
const groupByCol = ref('');
const orderByCol = ref('');
const orderDirection = ref('ASC');
const limitCount = ref(25);
const offsetCount = ref(0);

const executing = ref(false);
const error = ref('');
const results = ref([]);
const columns = ref([]);
const executionTimeMs = ref(null);

const activeDriver = computed(() => {
  return (store.driver || store.currentConnection || 'pgsql').toLowerCase();
});

const quoteChar = computed(() => {
  const drv = activeDriver.value;
  if (drv.includes('my') || drv.includes('maria')) return '`';
  if (drv.includes('sqlserver') || drv.includes('sqlsrv')) return '[';
  return '"';
});

const closeQuoteChar = computed(() => {
  const drv = activeDriver.value;
  if (drv.includes('sqlserver') || drv.includes('sqlsrv')) return ']';
  return quoteChar.value;
});

const wrapIdent = (ident) => {
  if (!ident || ident === '*') return ident;
  const qOpen = quoteChar.value;
  const qClose = closeQuoteChar.value;

  if (ident.includes('.')) {
    const parts = ident.split('.');
    return parts.map(p => `${qOpen}${p.replace(new RegExp(`[${qOpen}${qClose}]`, 'g'), '')}${qClose}`).join('.');
  }
  return `${qOpen}${ident.replace(new RegExp(`[${qOpen}${qClose}]`, 'g'), '')}${qClose}`;
};

const loadTables = async () => {
  try {
    const res = await store.scryFetch('/tables');
    if (res.ok) {
      const data = await res.json();
      availableTables.value = data.tables || [];
      if (availableTables.value.length > 0 && !activeTable.value) {
        activeTable.value = availableTables.value[0].name;
        await onPrimaryTableChange();
      }
    }
  } catch (err) {
    console.error(err);
  }
};

const onPrimaryTableChange = async () => {
  if (!activeTable.value) {
    availableColumns.value = [];
    selectedColumns.value = [];
    return;
  }

  try {
    const res = await store.scryFetch(`/tables/${activeTable.value}/schema`);
    if (res.ok) {
      const data = await res.json();
      availableColumns.value = (data.columns || []).map(c => ({
        name: c.name,
        fullName: `${activeTable.value}.${c.name}`,
        isPrimary: c.is_primary,
      }));
      selectedColumns.value = availableColumns.value.map(c => c.fullName);
    }
  } catch (err) {
    console.error(err);
  }
};

const selectAllColumns = () => {
  selectedColumns.value = availableColumns.value.map(c => c.fullName);
};

const deselectAllColumns = () => {
  selectedColumns.value = [];
};

const addJoinRow = () => {
  joins.value.push({
    type: 'INNER JOIN',
    table: '',
    localCol: 'id',
    foreignCol: 'id',
  });
};

const removeJoinRow = (idx) => {
  joins.value.splice(idx, 1);
};

const onJoinTableChange = async (j) => {
  if (!j.table) return;
  try {
    const res = await store.scryFetch(`/tables/${j.table}/schema`);
    if (res.ok) {
      const data = await res.json();
      const newCols = (data.columns || []).map(c => ({
        name: `${j.table}.${c.name}`,
        fullName: `${j.table}.${c.name}`,
        isPrimary: c.is_primary,
      }));
      // Append join columns to selection options
      availableColumns.value.push(...newCols);
    }
  } catch (err) {
    console.error(err);
  }
};

const addWhereRow = () => {
  whereClauses.value.push({
    logical: 'AND',
    column: availableColumns.value[0]?.name || '',
    operator: '=',
    value: '',
  });
};

const removeWhereRow = (idx) => {
  whereClauses.value.splice(idx, 1);
};

const generatedSql = computed(() => {
  if (!activeTable.value) return '';

  const drv = activeDriver.value;
  const isSqlServer = drv.includes('sqlsrv') || drv.includes('sqlserver');

  let selectCols = [];

  if (aggregateFunc.value) {
    const colTarget = aggregateCol.value === '*' ? '*' : wrapIdent(aggregateCol.value);
    selectCols.push(`${aggregateFunc.value}(${colTarget}) AS ${aggregateFunc.value.toLowerCase()}_result`);
  }

  if (selectedColumns.value.length > 0) {
    selectCols.push(...selectedColumns.value.map(wrapIdent));
  } else if (!aggregateFunc.value) {
    selectCols.push('*');
  }

  let sql = 'SELECT ';
  if (isDistinct.value) sql += 'DISTINCT ';

  // SQL Server TOP clause
  if (isSqlServer && limitCount.value > 0 && offsetCount.value === 0) {
    sql += `TOP ${limitCount.value} `;
  }

  sql += selectCols.join(',\n       ');
  sql += `\nFROM ${wrapIdent(activeTable.value)}`;

  // JOINs
  for (const j of joins.value) {
    if (j.table && j.localCol && j.foreignCol) {
      sql += `\n${j.type} ${wrapIdent(j.table)} ON ${wrapIdent(activeTable.value)}.${wrapIdent(j.localCol)} = ${wrapIdent(j.table)}.${wrapIdent(j.foreignCol)}`;
    }
  }

  // WHERE
  if (whereClauses.value.length > 0) {
    const whereStrings = whereClauses.value.map((w, idx) => {
      const col = wrapIdent(w.column);
      const prefix = idx > 0 ? `${w.logical} ` : '';

      if (w.operator.includes('NULL')) {
        return `${prefix}${col} ${w.operator}`;
      }

      if (w.operator === 'LIKE' || w.operator === 'NOT LIKE') {
        const val = w.value.replace(/'/g, "''");
        return `${prefix}${col} ${w.operator} '%${val}%'`;
      }

      if (w.operator === 'IN' || w.operator === 'NOT IN') {
        return `${prefix}${col} ${w.operator} (${w.value})`;
      }

      const escapedVal = isNaN(w.value) || w.value === '' ? `'${w.value.replace(/'/g, "''")}'` : w.value;
      return `${prefix}${col} ${w.operator} ${escapedVal}`;
    });

    sql += `\nWHERE ${whereStrings.join(' ')}`;
  }

  // GROUP BY
  if (groupByCol.value.trim()) {
    sql += `\nGROUP BY ${wrapIdent(groupByCol.value.trim())}`;
  }

  // ORDER BY
  if (orderByCol.value.trim()) {
    sql += `\nORDER BY ${wrapIdent(orderByCol.value.trim())} ${orderDirection.value}`;
  }

  // LIMIT & OFFSET for Postgres / MySQL / SQLite
  if (!isSqlServer) {
    if (limitCount.value > 0) sql += `\nLIMIT ${limitCount.value}`;
    if (offsetCount.value > 0) sql += ` OFFSET ${offsetCount.value}`;
  } else if (offsetCount.value > 0) {
    if (!orderByCol.value.trim()) {
      sql += `\nORDER BY (SELECT NULL)`;
    }
    sql += `\nOFFSET ${offsetCount.value} ROWS FETCH NEXT ${limitCount.value || 25} ROWS ONLY`;
  }

  return sql + ';';
});

const copyGeneratedSql = () => {
  if (!generatedSql.value) return;
  navigator.clipboard.writeText(generatedSql.value);
  toast.success('Generated SQL copied to clipboard!');
};

const openInConsole = () => {
  if (!generatedSql.value) return;
  router.push({
    name: 'query',
    query: { sql: generatedSql.value },
  });
};

const executeGeneratedSql = async () => {
  if (!generatedSql.value.trim()) return;

  executing.value = true;
  error.value = '';
  results.value = [];
  columns.value = [];
  executionTimeMs.value = null;

  try {
    const res = await store.scryFetch('/sql/execute', {
      method: 'POST',
      body: JSON.stringify({ query: generatedSql.value }),
    });

    const data = await res.json();
    if (!res.ok || data.error) {
      throw new Error(data.error || 'Query failed to execute.');
    }

    executionTimeMs.value = data.execution_time_ms || 0;
    results.value = data.data || [];
    columns.value = data.columns || (results.value.length > 0 ? Object.keys(results.value[0]) : []);
  } catch (err) {
    error.value = err.message;
  } finally {
    executing.value = false;
  }
};

onMounted(loadTables);
watch(() => store.currentConnection, loadTables);
</script>
