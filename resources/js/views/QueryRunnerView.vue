<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app">
    <!-- Top Bar -->
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 select-none">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Raw SQL Execution Console</h2>
        <p class="text-sm scry-text-muted">
          Monaco SQL editor with timing, execution history, bookmarking, and multi-format result viewer on 
          <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.
        </p>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="activeDrawer = activeDrawer === 'history' ? null : 'history'"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer flex items-center space-x-1.5"
          :class="activeDrawer === 'history' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-badge-glaucous'"
        >
          <span>History ({{ history.length }})</span>
        </button>

        <button
          @click="activeDrawer = activeDrawer === 'bookmarks' ? null : 'bookmarks'"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer flex items-center space-x-1.5"
          :class="activeDrawer === 'bookmarks' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-badge-pale-blue'"
        >
          <span>Bookmarks ({{ bookmarks.length }})</span>
        </button>

        <button
          @click="saveCurrentBookmark"
          :disabled="!currentSql.trim()"
          class="px-3 py-2 text-xs font-semibold rounded-lg scry-badge-sulphur transition-colors disabled:opacity-50 cursor-pointer"
        >
          + Bookmark
        </button>
      </div>
    </div>

    <!-- Main Workspace -->
    <div class="flex-1 flex space-x-4 overflow-hidden">
      <!-- Editor & Results Section -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Editor Input Card -->
        <div class="scry-bg-card border scry-border rounded-xl p-4 mb-4 shadow-sm flex flex-col">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div class="flex items-center space-x-2">
              <label class="text-xs font-bold uppercase tracking-wider scry-text-subtle">Monaco SQL Editor</label>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded font-bold scry-badge-glaucous uppercase">
                {{ activeDriver }} Dialect
              </span>
            </div>

            <!-- Database Snippets -->
            <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
              <button
                v-for="s in dbSnippets.slice(0, 3)"
                :key="s.label"
                @click="setEditorValue(s.sql)"
                class="px-2.5 py-1 rounded-md border scry-border scry-bg-input scry-text-main hover:scry-accent-text hover:border-pink-500/50 transition-all cursor-pointer font-mono font-medium"
                :title="s.desc"
              >
                {{ s.label }}
              </button>

              <div class="relative" v-if="dbSnippets.length > 3">
                <select
                  @change="onSnippetSelect"
                  class="px-2.5 py-1 text-[11px] rounded-md border scry-border scry-bg-input scry-accent-text font-mono font-bold focus:outline-none focus:ring-1 focus:ring-pink-500/50 cursor-pointer"
                >
                  <option value="" disabled selected>&plus; More {{ activeDriver.toUpperCase() }} Snippets...</option>
                  <option
                    v-for="(s, idx) in dbSnippets"
                    :key="idx"
                    :value="idx"
                  >
                    {{ s.label }} &mdash; {{ s.desc }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Monaco Container -->
          <div class="relative rounded-lg overflow-hidden border scry-border">
            <div ref="monacoContainer" class="w-full h-44 monaco-target"></div>
          </div>

          <!-- Editor Actions & Stats Footer -->
          <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs font-mono scry-text-muted flex items-center space-x-2">
              <span v-if="executionTimeMs !== null" class="px-2.5 py-0.5 rounded scry-badge-glaucous font-bold">
                ⚡ {{ executionTimeMs }} ms
              </span>
              <span v-if="resultsCount !== null" class="px-2 py-0.5 rounded scry-badge-pale-blue font-bold">
                {{ resultsCount }} row(s)
              </span>
              <span class="text-[11px] scry-text-subtle hidden md:inline">
                Press <kbd class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 rounded font-mono font-bold">⌘ + Enter</kbd> or <kbd class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 rounded font-mono font-bold">Ctrl + Enter</kbd> to execute
              </span>
            </div>

            <div class="flex items-center space-x-2">
              <button
                @click="formatSql"
                class="px-3 py-1.5 text-xs font-semibold rounded-lg border scry-border scry-text-main hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                title="Format & Prettify SQL"
              >
                Format SQL
              </button>

              <button
                @click="clearEditor"
                class="px-3 py-1.5 text-xs font-semibold rounded-lg border scry-border scry-text-main hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              >
                Clear
              </button>

              <button
                @click="runExplainQuery"
                :disabled="executing || !currentSql.trim()"
                class="px-3.5 py-1.5 text-xs font-semibold rounded-lg scry-badge-sulphur disabled:opacity-50 transition-colors cursor-pointer shadow-sm"
              >
                EXPLAIN
              </button>

              <button
                @click="runQuery"
                :disabled="executing || !currentSql.trim()"
                class="px-5 py-1.5 text-xs font-semibold rounded-lg scry-accent-bg disabled:opacity-50 transition-colors cursor-pointer shadow-sm flex items-center space-x-1"
              >
                <span>{{ executing ? 'Executing...' : 'Run Query' }}</span>
                <span class="text-[10px] opacity-75 font-mono">(⌘↵)</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Output & Results Card -->
        <div class="flex-1 overflow-hidden scry-bg-card border scry-border rounded-xl p-4 shadow-sm flex flex-col">
          <!-- View Toggle & Result Meta Bar -->
          <div v-if="results.length > 0 || affectedRows !== null || error" class="mb-3 flex items-center justify-between border-b scry-border pb-2">
            <div class="flex items-center space-x-2">
              <span class="text-xs font-bold uppercase tracking-wider scry-text-subtle">Results Output</span>
              <span v-if="executionTimeMs !== null" class="text-xs font-mono scry-text-muted">
                (Executed in <strong class="scry-accent-text">{{ executionTimeMs }}ms</strong>)
              </span>
            </div>

            <div class="flex items-center space-x-2" v-if="results.length > 0">
              <div class="flex items-center p-0.5 rounded-lg scry-bg-input border scry-border">
                <button
                  @click="viewMode = 'grid'"
                  class="px-3 py-1 text-xs font-semibold rounded-md transition-all cursor-pointer"
                  :class="viewMode === 'grid' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-text-muted hover:scry-text-main'"
                >
                  Table Grid
                </button>
                <button
                  @click="viewMode = 'json'"
                  class="px-3 py-1 text-xs font-semibold rounded-md transition-all cursor-pointer"
                  :class="viewMode === 'json' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-text-muted hover:scry-text-main'"
                >
                  JSON View
                </button>
              </div>

              <button
                @click="copyResultsJSON"
                class="px-2.5 py-1 text-[11px] font-semibold rounded scry-badge-pale-blue hover:opacity-80 transition-opacity cursor-pointer font-mono"
              >
                Copy JSON
              </button>
              <button
                @click="exportResultsCsv"
                class="px-2.5 py-1 text-[11px] font-semibold rounded scry-badge-glaucous hover:opacity-80 transition-opacity cursor-pointer font-mono"
              >
                Export CSV
              </button>
            </div>
          </div>

          <!-- Error Alert -->
          <div v-if="error" class="p-4 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-mono">
            <strong>Database Execution Error:</strong>
            <pre class="mt-1 whitespace-pre-wrap">{{ error }}</pre>
          </div>

          <!-- Non-SELECT Affected Rows Alert -->
          <div v-else-if="affectedRows !== null" class="p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-mono font-bold">
            ✓ Statement executed successfully. {{ affectedRows }} row(s) affected.
          </div>

          <!-- Results Tabular Grid -->
          <div v-else-if="results.length > 0 && viewMode === 'grid'" class="flex-1 overflow-auto">
            <table class="w-full text-left text-xs font-mono select-none">
              <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider sticky top-0 z-10">
                <tr>
                  <th
                    v-for="col in columns"
                    :key="col"
                    @click="sortColumn(col)"
                    class="px-4 py-2.5 cursor-pointer hover:scry-accent-text transition-colors"
                  >
                    <div class="flex items-center space-x-1">
                      <span>{{ col }}</span>
                      <span v-if="sortBy === col" class="scry-accent-text font-bold">
                        {{ sortDir === 'asc' ? '▲' : '▼' }}
                      </span>
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y scry-border-subtle scry-text-main">
                <tr v-for="(row, i) in sortedResults" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                  <td v-for="col in columns" :key="col" class="px-4 py-2 scry-text-main max-w-xs truncate" :title="String(row[col])">
                    <span v-if="row[col] === null" class="text-slate-400 dark:text-slate-600 italic">NULL</span>
                    <span v-else-if="typeof row[col] === 'boolean'" class="px-1.5 py-0.5 rounded text-[10px] font-bold" :class="row[col] ? 'scry-badge-glaucous' : 'bg-rose-500/15 text-rose-600 dark:text-rose-400'">
                      {{ row[col] ? 'TRUE' : 'FALSE' }}
                    </span>
                    <span v-else-if="typeof row[col] === 'object'" class="font-mono text-[11px] opacity-80">
                      {{ JSON.stringify(row[col]) }}
                    </span>
                    <span v-else>{{ row[col] }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Results Formatted JSON View -->
          <div v-else-if="results.length > 0 && viewMode === 'json'" class="flex-1 overflow-auto p-3 rounded-lg scry-bg-input border scry-border font-mono text-xs text-emerald-600 dark:text-emerald-400">
            <pre class="whitespace-pre-wrap">{{ JSON.stringify(results, null, 2) }}</pre>
          </div>

          <!-- Empty State -->
          <div v-else class="flex-1 flex items-center justify-center text-center text-xs scry-text-muted font-mono">
            Execute a raw SQL query or snippet above to view results.
          </div>
        </div>
      </div>

      <!-- Right Drawer Sidebar (History & Bookmarks) -->
      <div v-if="activeDrawer" class="w-80 scry-bg-card border scry-border rounded-xl p-4 shadow-sm flex flex-col justify-between overflow-y-auto">
        <!-- History View -->
        <div v-if="activeDrawer === 'history'">
          <div class="flex items-center justify-between border-b scry-border pb-2 mb-3">
            <div class="flex items-center space-x-2">
              <h3 class="font-bold text-xs uppercase tracking-wider scry-text-main">Query History</h3>
              <span class="text-[10px] px-1.5 py-0.5 rounded scry-badge-glaucous font-bold">{{ history.length }}</span>
            </div>
            <div class="flex items-center space-x-2">
              <button @click="clearHistory" class="text-[11px] text-rose-500 hover:underline cursor-pointer">Clear</button>
              <button @click="activeDrawer = null" class="text-xs scry-text-muted font-bold cursor-pointer">&times;</button>
            </div>
          </div>

          <div v-if="history.length === 0" class="text-xs text-center scry-text-muted py-6 font-mono">
            No execution history yet.
          </div>

          <div class="space-y-2 max-h-[calc(100vh-280px)] overflow-y-auto">
            <div
              v-for="(h, idx) in history"
              :key="idx"
              class="p-2.5 rounded-lg border scry-border scry-bg-input text-xs font-mono space-y-1.5"
            >
              <div class="flex items-center justify-between text-[10px] scry-text-subtle">
                <span>{{ h.time }}</span>
                <span class="px-1.5 py-0.2 rounded font-bold" :class="h.success ? 'scry-badge-glaucous' : 'bg-rose-500/20 text-rose-500'">
                  {{ h.duration }}ms
                </span>
              </div>
              <p class="scry-text-main line-clamp-2 text-[11px] break-all">{{ h.sql }}</p>
              <div class="flex items-center space-x-1 pt-1">
                <button
                  @click="setEditorValue(h.sql)"
                  class="flex-1 text-center px-2 py-1 rounded scry-badge-pale-blue text-[10px] font-semibold cursor-pointer"
                >
                  Load
                </button>
                <button
                  @click="setEditorValue(h.sql); runQuery()"
                  class="flex-1 text-center px-2 py-1 rounded scry-accent-bg text-[10px] font-semibold cursor-pointer"
                >
                  Re-run
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Bookmarks View -->
        <div v-else-if="activeDrawer === 'bookmarks'">
          <div class="flex items-center justify-between border-b scry-border pb-2 mb-3">
            <div class="flex items-center space-x-2">
              <h3 class="font-bold text-xs uppercase tracking-wider scry-text-main">Saved Bookmarks</h3>
              <span class="text-[10px] px-1.5 py-0.5 rounded scry-badge-pale-blue font-bold">{{ bookmarks.length }}</span>
            </div>
            <button @click="activeDrawer = null" class="text-xs scry-text-muted font-bold cursor-pointer">&times;</button>
          </div>

          <div v-if="bookmarks.length === 0" class="text-xs text-center scry-text-muted py-6 font-mono">
            No saved bookmarks yet.
          </div>

          <div class="space-y-2 max-h-[calc(100vh-280px)] overflow-y-auto">
            <div
              v-for="(b, idx) in bookmarks"
              :key="idx"
              class="p-2.5 rounded-lg border scry-border scry-bg-input text-xs font-mono space-y-1.5"
            >
              <div class="flex items-center justify-between font-bold scry-accent-text">
                <span>{{ b.title }}</span>
                <button @click="removeBookmark(idx)" class="text-rose-500 hover:underline text-[10px] cursor-pointer">&times;</button>
              </div>
              <p class="scry-text-muted line-clamp-2 text-[11px] break-all">{{ b.sql }}</p>
              <button
                @click="setEditorValue(b.sql)"
                class="w-full text-center px-2 py-1 rounded scry-badge-glaucous text-[10px] font-semibold cursor-pointer"
              >
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useConnectionStore } from '../stores/useConnectionStore';
import { useToastStore } from '../stores/useToastStore';
import * as monaco from 'monaco-editor';

const store = useConnectionStore();
const toast = useToastStore();
const route = useRoute();

const monacoContainer = ref(null);
let editorInstance = null;

const currentSql = ref('');
const executing = ref(false);
const error = ref('');
const results = ref([]);
const columns = ref([]);
const affectedRows = ref(null);
const executionTimeMs = ref(null);
const resultsCount = ref(null);

const viewMode = ref('grid'); // 'grid' | 'json'
const activeDrawer = ref(null); // 'history' | 'bookmarks' | null

const sortBy = ref(null);
const sortDir = ref('asc');

const history = ref(JSON.parse(localStorage.getItem('scry-sql-history') || '[]'));
const bookmarks = ref(JSON.parse(localStorage.getItem('scry-sql-bookmarks') || '[]'));

const activeDriver = computed(() => {
  return (store.driver || store.currentConnection || 'pgsql').toLowerCase();
});

const sortedResults = computed(() => {
  if (!sortBy.value || !results.value.length) return results.value;
  const col = sortBy.value;
  const dir = sortDir.value === 'desc' ? -1 : 1;

  return [...results.value].sort((a, b) => {
    const valA = a[col];
    const valB = b[col];
    if (valA === valB) return 0;
    if (valA === null || valA === undefined) return 1;
    if (valB === null || valB === undefined) return -1;
    return valA > valB ? dir : -dir;
  });
});

const sortColumn = (col) => {
  if (sortBy.value === col) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = col;
    sortDir.value = 'asc';
  }
};

const dbSnippets = computed(() => {
  const drv = activeDriver.value;

  if (drv.includes('pg')) {
    return [
      {
        label: 'SHOW Tables & Sizes',
        desc: 'List all public tables, total disk size, and estimated row count',
        sql: `SELECT table_name,\n       pg_size_pretty(pg_total_relation_size(quote_ident(table_name))) AS total_size,\n       n_live_tup AS estimated_rows\nFROM information_schema.tables t\nJOIN pg_stat_user_tables s ON s.relname = t.table_name\nWHERE table_schema = 'public'\nORDER BY pg_total_relation_size(quote_ident(table_name)) DESC;`,
      },
      {
        label: 'Active Processes',
        desc: 'Inspect active running queries & sessions',
        sql: `SELECT pid,\n       usename,\n       client_addr,\n       state,\n       now() - query_start AS duration,\n       query\nFROM pg_stat_activity\nWHERE state != 'idle'\nORDER BY duration DESC;`,
      },
      {
        label: 'Foreign Key Map',
        desc: 'Query foreign key constraints & relationships',
        sql: `SELECT tc.table_name,\n       kcu.column_name,\n       ccu.table_name AS foreign_table_name,\n       ccu.column_name AS foreign_column_name\nFROM information_schema.table_constraints tc\nJOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name\nJOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name\nWHERE tc.constraint_type = 'FOREIGN KEY';`,
      },
      {
        label: 'SELECT Users (Limit 10)',
        desc: 'Basic user query',
        sql: `SELECT * FROM users ORDER BY id DESC LIMIT 10;`,
      },
      {
        label: 'Join Posts & Authors',
        desc: 'Fetch recent posts joined with author names',
        sql: `SELECT p.id,\n       p.title,\n       p.is_published,\n       u.name AS author_name\nFROM posts p\nJOIN users u ON p.user_id = u.id\nORDER BY p.id DESC\nLIMIT 10;`,
      },
    ];
  } else if (drv.includes('my') || drv.includes('maria')) {
    return [
      {
        label: 'SHOW TABLES & Disk Usage',
        desc: 'List tables with data & index sizes in MB',
        sql: `SELECT table_name,\n       round(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,\n       table_rows\nFROM information_schema.tables\nWHERE table_schema = DATABASE()\nORDER BY (data_length + index_length) DESC;`,
      },
      {
        label: 'SHOW FULL PROCESSLIST',
        desc: 'Inspect running MySQL client connections & queries',
        sql: `SHOW FULL PROCESSLIST;`,
      },
      {
        label: 'Foreign Key Constraints',
        desc: 'Inspect foreign key relationships in current database',
        sql: `SELECT TABLE_NAME,\n       COLUMN_NAME,\n       CONSTRAINT_NAME,\n       REFERENCED_TABLE_NAME,\n       REFERENCED_COLUMN_NAME\nFROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE\nWHERE REFERENCED_TABLE_SCHEMA = DATABASE()\n  AND REFERENCED_TABLE_NAME IS NOT NULL;`,
      },
      {
        label: 'SELECT Users (Limit 10)',
        desc: 'Basic user query',
        sql: `SELECT * FROM users ORDER BY id DESC LIMIT 10;`,
      },
      {
        label: 'Join Posts & Authors',
        desc: 'Fetch recent posts joined with author names',
        sql: `SELECT p.id,\n       p.title,\n       p.is_published,\n       u.name AS author_name\nFROM posts p\nJOIN users u ON p.user_id = u.id\nORDER BY p.id DESC\nLIMIT 10;`,
      },
    ];
  } else if (drv.includes('sqlite')) {
    return [
      {
        label: 'List Master Tables',
        desc: 'Show all user tables in SQLite database',
        sql: `SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') AND name NOT LIKE 'sqlite_%' ORDER BY name;`,
      },
      {
        label: 'Table DDL Definitions',
        desc: 'Show CREATE TABLE SQL statements',
        sql: `SELECT name, sql FROM sqlite_master WHERE type = 'table';`,
      },
      {
        label: 'SELECT Users (Limit 10)',
        desc: 'Basic user query',
        sql: `SELECT * FROM users ORDER BY id DESC LIMIT 10;`,
      },
      {
        label: 'Join Posts & Authors',
        desc: 'Fetch recent posts joined with author names',
        sql: `SELECT p.id,\n       p.title,\n       p.is_published,\n       u.name AS author_name\nFROM posts p\nJOIN users u ON p.user_id = u.id\nORDER BY p.id DESC\nLIMIT 10;`,
      },
    ];
  } else {
    // SQL Server / Fallback
    return [
      {
        label: 'Table Space & Row Counts',
        desc: 'Show tables and allocated MB space',
        sql: `SELECT t.name AS TableName,\n       p.rows AS RowCounts,\n       CAST(ROUND(((SUM(a.total_pages) * 8) / 1024.0), 2) AS NUMERIC(36, 2)) AS TotalSpaceMB\nFROM sys.tables t\nINNER JOIN sys.indexes i ON t.OBJECT_ID = i.object_id\nINNER JOIN sys.partitions p ON i.object_id = p.OBJECT_ID AND i.index_id = p.index_id\nINNER JOIN sys.allocation_units a ON p.partition_id = a.container_id\nGROUP BY t.Name, p.Rows\nORDER BY TotalSpaceMB DESC;`,
      },
      {
        label: 'SELECT Users (Limit 10)',
        desc: 'Basic user query',
        sql: `SELECT TOP 10 * FROM users ORDER BY id DESC;`,
      },
      {
        label: 'Join Posts & Authors',
        desc: 'Fetch recent posts joined with author names',
        sql: `SELECT TOP 10 p.id,\n       p.title,\n       u.name AS author_name\nFROM posts p\nJOIN users u ON p.user_id = u.id\nORDER BY p.id DESC;`,
      },
    ];
  }
});

const setEditorValue = (val) => {
  currentSql.value = val;
  if (editorInstance) {
    editorInstance.setValue(val);
  }
};

const clearEditor = () => {
  setEditorValue('');
};

const onSnippetSelect = (event) => {
  const idx = event.target.value;
  if (idx !== '' && dbSnippets.value[idx]) {
    setEditorValue(dbSnippets.value[idx].sql);
  }
};

const formatSql = () => {
  if (!currentSql.value.trim()) return;
  let s = currentSql.value.trim();

  const keywords = [
    'select', 'from', 'where', 'left join', 'right join', 'inner join', 'full outer join', 'join', 
    'group by', 'order by', 'limit', 'offset', 'having', 'insert into', 'values', 'update', 'set', 
    'delete from', 'create table', 'alter table', 'drop table'
  ];
  for (const kw of keywords) {
    const reg = new RegExp(`\\b${kw}\\b`, 'gi');
    s = s.replace(reg, (match) => `\n${match.toUpperCase()}`);
  }
  setEditorValue(s.trim());
};

const runExplainQuery = async () => {
  if (!currentSql.value.trim()) return;
  const original = currentSql.value.trim();
  const explainSql = original.toUpperCase().startsWith('EXPLAIN') ? original : `EXPLAIN ${original}`;
  setEditorValue(explainSql);
  await runQuery();
};

const runQuery = async () => {
  if (!currentSql.value.trim()) return;

  executing.value = true;
  error.value = '';
  results.value = [];
  columns.value = [];
  affectedRows.value = null;
  executionTimeMs.value = null;
  resultsCount.value = null;

  const startTime = Date.now();
  const sqlToExecute = currentSql.value.trim();

  try {
    const res = await store.scryFetch('/sql/execute', {
      method: 'POST',
      body: JSON.stringify({ query: sqlToExecute }),
    });

    const data = await res.json();
    if (!res.ok || data.error) {
      throw new Error(data.error || 'Execution failed');
    }

    executionTimeMs.value = data.execution_time_ms || (Date.now() - startTime);

    if (data.is_read || data.query_type === 'SELECT' || data.query_type === 'EXPLAIN' || data.data) {
      results.value = data.data || [];
      columns.value = data.columns || (results.value.length > 0 ? Object.keys(results.value[0]) : []);
      resultsCount.value = results.value.length;
    } else {
      affectedRows.value = data.affected_rows ?? 0;
    }

    // Save to history stack
    recordHistory(sqlToExecute, executionTimeMs.value, true);
  } catch (err) {
    error.value = err.message;
    executionTimeMs.value = Date.now() - startTime;
    recordHistory(sqlToExecute, executionTimeMs.value, false);
  } finally {
    executing.value = false;
  }
};

const recordHistory = (sql, duration, success) => {
  const item = {
    sql,
    duration: Math.round(duration),
    success,
    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
  };

  history.value.unshift(item);
  if (history.value.length > 50) history.value.pop();
  localStorage.setItem('scry-sql-history', JSON.stringify(history.value));
};

const clearHistory = () => {
  history.value = [];
  localStorage.removeItem('scry-sql-history');
};

const copyResultsJSON = () => {
  navigator.clipboard.writeText(JSON.stringify(results.value, null, 2));
  toast.success('Query results JSON copied to clipboard!');
};

const exportResultsCsv = () => {
  if (!results.value.length) return;
  const cols = columns.value;
  const csvRows = [cols.join(',')];

  for (const row of results.value) {
    const values = cols.map(c => {
      const val = row[c];
      if (val === null || val === undefined) return '';
      const str = typeof val === 'object' ? JSON.stringify(val) : String(val);
      return `"${str.replace(/"/g, '""')}"`;
    });
    csvRows.push(values.join(','));
  }

  const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', `query_results_${Date.now()}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  toast.success('Results exported as CSV.');
};

const saveCurrentBookmark = () => {
  const title = prompt('Enter title for this SQL bookmark:', 'Saved Query');
  if (!title) return;

  bookmarks.value.push({ title, sql: currentSql.value });
  localStorage.setItem('scry-sql-bookmarks', JSON.stringify(bookmarks.value));
  activeDrawer.value = 'bookmarks';
  toast.success('Bookmark saved.');
};

const removeBookmark = (index) => {
  bookmarks.value.splice(index, 1);
  localStorage.setItem('scry-sql-bookmarks', JSON.stringify(bookmarks.value));
};

const initMonaco = () => {
  if (!monacoContainer.value) return;

  const isDarkMode = document.documentElement.classList.contains('dark');

  editorInstance = monaco.editor.create(monacoContainer.value, {
    value: currentSql.value,
    language: 'sql',
    theme: isDarkMode ? 'vs-dark' : 'vs',
    minimap: { enabled: false },
    lineNumbers: 'on',
    scrollBeyondLastLine: false,
    fontSize: 13,
    fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
    automaticLayout: true,
    tabSize: 2,
    wordWrap: 'on',
  });

  editorInstance.onDidChangeModelContent(() => {
    currentSql.value = editorInstance.getValue();
  });

  // Keyboard shortcut: Cmd+Enter / Ctrl+Enter to execute
  editorInstance.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.Enter, () => {
    runQuery();
  });
};

onMounted(() => {
  if (route.query.sql) {
    currentSql.value = route.query.sql;
  }
  initMonaco();
});

onUnmounted(() => {
  if (editorInstance) {
    editorInstance.dispose();
  }
});
</script>

<style scoped>
.monaco-target {
  min-height: 180px;
}
</style>
