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
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div class="flex items-center space-x-2">
              <label class="text-xs font-bold uppercase tracking-wider scry-text-subtle">SQL Command Editor</label>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded font-bold scry-badge-glaucous">
                {{ activeDriver.toUpperCase() }} Snippets
              </span>
            </div>

            <!-- Database-specific snippet quick buttons & dropdown -->
            <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
              <button
                v-for="s in dbSnippets.slice(0, 3)"
                :key="s.label"
                @click="query = s.sql"
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

          <textarea
            v-model="query"
            @keydown.cmd.enter.prevent="runQuery"
            @keydown.ctrl.enter.prevent="runQuery"
            rows="6"
            :placeholder="`Type raw ${activeDriver.toUpperCase()} query here... Press ⌘+Enter to execute.`"
            class="w-full scry-bg-input border scry-border rounded-lg p-3 text-xs font-mono scry-accent-text focus:outline-none focus:ring-2 focus:ring-pink-500/50 shadow-inner"
          ></textarea>

          <div class="mt-3 flex items-center justify-between">
            <div class="text-xs font-mono scry-text-muted flex items-center space-x-2">
              <span v-if="executionTimeMs !== null" class="px-2 py-0.5 rounded scry-badge-glaucous font-bold">⚡ {{ executionTimeMs }} ms</span>
              <span class="text-[11px] scry-text-subtle">Press <kbd class="px-1 py-0.5 bg-slate-200 dark:bg-slate-700 rounded font-mono">⌘ + Enter</kbd> to run</span>
            </div>

            <div class="flex space-x-2">
              <button
                @click="query = ''"
                class="px-3 py-1.5 text-xs font-semibold rounded-lg border scry-border scry-text-main hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
              >
                Clear
              </button>
              <button
                @click="runQuery"
                :disabled="executing || !query.trim()"
                class="px-5 py-1.5 text-xs font-semibold rounded-lg scry-accent-bg disabled:opacity-50 transition-colors cursor-pointer shadow-sm focus:outline-none focus:ring-2 focus:ring-pink-500/50 flex items-center space-x-1"
              >
                <span>{{ executing ? 'Executing...' : 'Run Query' }}</span>
                <span class="text-[10px] opacity-75 font-mono">(⌘↵)</span>
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
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-mono scry-text-muted font-bold">{{ results.length }} record(s) returned</span>
              <button
                @click="copyResultsJSON"
                class="px-2.5 py-1 text-[11px] font-semibold rounded scry-badge-pale-blue hover:opacity-80 transition-opacity cursor-pointer font-mono"
              >
                Copy JSON Output
              </button>
            </div>
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
import { ref, computed, onMounted } from 'vue';
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

const activeDriver = computed(() => {
  return (store.driver || store.currentConnection || 'pgsql').toLowerCase();
});

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
        label: 'JSON Field Aggregation',
        desc: 'Extract JSON key and group user counts',
        sql: `SELECT settings->>'theme' AS theme,\n       count(*) AS user_count\nFROM users\nWHERE settings IS NOT NULL\nGROUP BY settings->>'theme';`,
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
        label: 'JSON Extract & Grouping',
        desc: 'Extract JSON attribute with JSON_UNQUOTE',
        sql: `SELECT JSON_UNQUOTE(JSON_EXTRACT(settings, '$.theme')) AS theme,\n       count(*) AS user_count\nFROM users\nGROUP BY theme;`,
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

const onSnippetSelect = (event) => {
  const idx = event.target.value;
  if (idx !== '' && dbSnippets.value[idx]) {
    query.value = dbSnippets.value[idx].sql;
  }
};

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

const copyResultsJSON = () => {
  navigator.clipboard.writeText(JSON.stringify(results.value, null, 2));
  alert('Query results JSON copied to clipboard!');
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
