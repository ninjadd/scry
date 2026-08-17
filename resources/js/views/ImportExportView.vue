<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 select-none">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Streaming Data Import & Export Hub</h2>
        <p class="text-sm scry-text-muted">
          Transactional SQL/CSV imports and memory-safe streaming exports (CSV, SQL, XML, JSON) for 
          <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.
        </p>
      </div>

      <div class="flex items-center space-x-2">
        <div class="flex items-center p-0.5 rounded-lg scry-bg-input border scry-border">
          <button
            @click="activeTab = 'export'"
            class="px-4 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer"
            :class="activeTab === 'export' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-text-muted hover:scry-text-main'"
          >
            Export Table Data
          </button>
          <button
            @click="activeTab = 'import'"
            class="px-4 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer"
            :class="activeTab === 'import' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-text-muted hover:scry-text-main'"
          >
            Transactional Import
          </button>
        </div>
      </div>
    </div>

    <!-- Export Tab -->
    <div v-if="activeTab === 'export'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Config Form -->
      <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm space-y-4 font-mono text-xs">
        <h3 class="font-bold text-sm uppercase tracking-wider scry-text-main pb-2 border-b scry-border">
          1. Export Settings
        </h3>

        <!-- Table Selector -->
        <div>
          <label class="block mb-1 text-slate-400 font-bold">Source Table</label>
          <select
            v-model="selectedTable"
            class="w-full scry-bg-input border scry-border rounded-lg p-2.5 scry-text-main focus:outline-none focus:ring-2 focus:ring-pink-500 font-mono"
          >
            <option disabled value="">-- Select Table to Export --</option>
            <option v-for="t in tables" :key="t.name" :value="t.name">
              {{ t.name }} ({{ t.rows }} rows)
            </option>
          </select>
        </div>

        <!-- Format Selector -->
        <div>
          <label class="block mb-1 text-slate-400 font-bold">Export Format</label>
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="fmt in ['csv', 'sql', 'json', 'xml']"
              :key="fmt"
              @click="exportFormat = fmt"
              class="p-2.5 rounded-lg border text-center font-bold uppercase transition-all cursor-pointer"
              :class="exportFormat === fmt ? 'scry-accent-bg font-bold shadow-sm' : 'scry-bg-input border-transparent scry-text-muted hover:scry-text-main'"
            >
              {{ fmt }}
            </button>
          </div>
        </div>

        <!-- Options -->
        <div class="space-y-2 pt-2 border-t scry-border">
          <label v-if="exportFormat === 'sql'" class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" v-model="includeDropTable" class="rounded text-pink-600" />
            <span class="scry-text-main">Include DROP TABLE IF EXISTS</span>
          </label>

          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" v-model="limitRows" class="rounded text-pink-600" />
            <span class="scry-text-main">Limit records (first 1000)</span>
          </label>
        </div>

        <!-- Action Button -->
        <div class="pt-4">
          <button
            @click="triggerExport"
            :disabled="!selectedTable || exporting"
            class="w-full py-3 rounded-lg scry-accent-bg font-bold disabled:opacity-50 transition-opacity cursor-pointer shadow-md text-sm flex items-center justify-center space-x-2"
          >
            <span>{{ exporting ? 'Generating Export...' : `Download ${exportFormat.toUpperCase()} Export` }}</span>
          </button>
        </div>
      </div>

      <!-- Right Description / Specs -->
      <div class="lg:col-span-2 scry-bg-card border scry-border rounded-xl p-5 shadow-sm space-y-4 font-mono text-xs">
        <h3 class="font-bold text-sm uppercase tracking-wider scry-text-main pb-2 border-b scry-border">
          Format Specifications & Compatibility
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-4 rounded-lg scry-bg-input border scry-border space-y-2">
            <span class="font-bold scry-accent-text text-sm">CSV Stream</span>
            <p class="text-slate-400">Streams raw rows directly with standard RFC 4180 CSV compliance and encoded JSON column preservation.</p>
          </div>

          <div class="p-4 rounded-lg scry-bg-input border scry-border space-y-2">
            <span class="font-bold scry-accent-text text-sm">SQL Dump</span>
            <p class="text-slate-400">Generates dialect-accurate `INSERT INTO` statements configured specifically for {{ store.currentConnection }}.</p>
          </div>

          <div class="p-4 rounded-lg scry-bg-input border scry-border space-y-2">
            <span class="font-bold scry-accent-text text-sm">Formatted JSON</span>
            <p class="text-slate-400">Pretty-printed JSON object including row counts, export timestamps, and normalized typed record collections.</p>
          </div>

          <div class="p-4 rounded-lg scry-bg-input border scry-border space-y-2">
            <span class="font-bold scry-accent-text text-sm">Hierarchical XML</span>
            <p class="text-slate-400">XML document schema with individual &lt;record&gt; nodes for third-party ETL and legacy pipeline interoperability.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Import Tab -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6 font-mono text-xs">
      <!-- Left Import Controls -->
      <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm space-y-4">
        <h3 class="font-bold text-sm uppercase tracking-wider scry-text-main pb-2 border-b scry-border">
          1. Upload & Transaction Options
        </h3>

        <!-- Import Type -->
        <div>
          <label class="block mb-1 text-slate-400 font-bold">Import Type</label>
          <div class="grid grid-cols-2 gap-2">
            <button
              @click="importType = 'sql'"
              class="p-2 rounded-lg border text-center font-bold uppercase transition-all cursor-pointer"
              :class="importType === 'sql' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-bg-input border-transparent scry-text-muted'"
            >
              SQL Script (.sql)
            </button>
            <button
              @click="importType = 'csv'"
              class="p-2 rounded-lg border text-center font-bold uppercase transition-all cursor-pointer"
              :class="importType === 'csv' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-bg-input border-transparent scry-text-muted'"
            >
              CSV Data (.csv)
            </button>
          </div>
        </div>

        <!-- Target Table (For CSV only) -->
        <div v-if="importType === 'csv'">
          <label class="block mb-1 text-slate-400 font-bold">Target Table</label>
          <select
            v-model="targetImportTable"
            class="w-full scry-bg-input border scry-border rounded-lg p-2.5 scry-text-main focus:outline-none focus:ring-2 focus:ring-pink-500 font-mono"
          >
            <option disabled value="">-- Select Destination Table --</option>
            <option v-for="t in tables" :key="t.name" :value="t.name">
              {{ t.name }}
            </option>
          </select>
        </div>

        <!-- Safety Notice -->
        <div class="p-3 rounded-lg bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400 space-y-1">
          <span class="font-bold flex items-center space-x-1">
            <span>🛡️</span>
            <span>Automatic Transaction Rollback</span>
          </span>
          <p class="text-[11px] leading-relaxed">
            All statements and batches execute inside an isolated transaction. If any error occurs, all changes are automatically rolled back.
          </p>
        </div>

        <!-- Execute Button -->
        <div class="pt-2">
          <button
            @click="executeImport"
            :disabled="!fileContent || importing || (importType === 'csv' && !targetImportTable)"
            class="w-full py-3 rounded-lg scry-accent-bg font-bold disabled:opacity-50 transition-opacity cursor-pointer shadow-md text-sm flex items-center justify-center space-x-2"
          >
            <span>{{ importing ? 'Executing Transaction...' : 'Run Transactional Import' }}</span>
          </button>
        </div>
      </div>

      <!-- Right File Input & Status -->
      <div class="lg:col-span-2 space-y-4">
        <!-- File Dropzone -->
        <div
          class="scry-bg-card border-2 border-dashed scry-border rounded-xl p-8 text-center flex flex-col items-center justify-center cursor-pointer hover:border-pink-500 transition-colors shadow-sm relative"
          @click="$refs.fileInput.click()"
          @dragover.prevent
          @drop.prevent="onFileDrop"
        >
          <input
            ref="fileInput"
            type="file"
            :accept="importType === 'sql' ? '.sql' : '.csv'"
            class="hidden"
            @change="onFileSelected"
          />

          <div class="text-3xl mb-2">📁</div>
          <span class="font-bold text-sm scry-text-main mb-1">
            {{ fileName ? fileName : `Click to browse or drop ${importType.toUpperCase()} file here` }}
          </span>
          <p class="text-xs scry-text-muted">
            {{ fileContent ? `${fileContent.length.toLocaleString()} characters loaded` : 'Supports standard SQL dumps and UTF-8 CSV datasets.' }}
          </p>
        </div>

        <!-- Transaction Result Report -->
        <div v-if="importResult" class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between border-b scry-border pb-2">
            <h4 class="font-bold text-sm scry-text-main">Transaction Execution Report</h4>
            <span
              class="px-2.5 py-0.5 rounded text-xs font-bold uppercase"
              :class="importResult.success ? 'scry-badge-glaucous' : 'bg-rose-500/20 text-rose-500'"
            >
              {{ importResult.success ? 'COMMITTED (SUCCESS)' : 'ROLLED BACK (ABORTED)' }}
            </span>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="p-3 rounded-lg scry-bg-input border scry-border">
              <span class="text-slate-400 block text-[10px]">Statements/Rows Executed</span>
              <span class="text-base font-bold scry-text-main">
                {{ importResult.executed_statements || importResult.inserted_rows || 0 }}
              </span>
            </div>

            <div class="p-3 rounded-lg scry-bg-input border scry-border">
              <span class="text-slate-400 block text-[10px]">Total In Payload</span>
              <span class="text-base font-bold scry-text-main">
                {{ importResult.total_statements || importResult.inserted_rows || 0 }}
              </span>
            </div>

            <div class="p-3 rounded-lg scry-bg-input border scry-border">
              <span class="text-slate-400 block text-[10px]">Transaction Status</span>
              <span class="text-xs font-bold" :class="importResult.transaction_committed ? 'text-emerald-500' : 'text-rose-500'">
                {{ importResult.transaction_committed ? 'COMMITTED' : 'ROLLED BACK' }}
              </span>
            </div>
          </div>

          <!-- Error Details -->
          <div v-if="importResult.error" class="p-3 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-500 space-y-1">
            <span class="font-bold block">Error Statement #{{ importResult.failed_statement_index }}:</span>
            <p class="font-mono text-xs break-all">{{ importResult.error }}</p>
            <div v-if="importResult.failed_statement" class="mt-2 p-2 bg-black/40 rounded text-slate-300 font-mono text-[11px] truncate">
              {{ importResult.failed_statement }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';
import { useToastStore } from '../stores/useToastStore';

const store = useConnectionStore();
const toast = useToastStore();

const activeTab = ref('export'); // 'export' | 'import'
const tables = ref([]);

// Export State
const selectedTable = ref('');
const exportFormat = ref('csv');
const includeDropTable = ref(false);
const limitRows = ref(false);
const exporting = ref(false);

// Import State
const importType = ref('sql'); // 'sql' | 'csv'
const targetImportTable = ref('');
const fileInput = ref(null);
const fileName = ref('');
const fileContent = ref('');
const importing = ref(false);
const importResult = ref(null);

const loadTables = async () => {
  try {
    const res = await store.scryFetch('/tables');
    if (res.ok) {
      const data = await res.json();
      tables.value = data.tables || [];
      if (!selectedTable.value && tables.value.length > 0) {
        selectedTable.value = tables.value[0].name;
      }
    }
  } catch (err) {
    console.error(err);
  }
};

const triggerExport = async () => {
  if (!selectedTable.value) return;
  exporting.value = true;

  try {
    const query = new URLSearchParams({
      format: exportFormat.value,
      drop_table: includeDropTable.value ? '1' : '0',
    });

    const url = `${store.apiUrl(`/export/${selectedTable.value}`)}?${query.toString()}`;
    window.open(url, '_blank');
    toast.success(`Export for table ${selectedTable.value} started.`);
  } catch (err) {
    toast.error('Export failed: ' + err.message);
  } finally {
    exporting.value = false;
  }
};

const onFileSelected = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  readFile(file);
};

const onFileDrop = (e) => {
  const file = e.dataTransfer.files[0];
  if (!file) return;
  readFile(file);
};

const readFile = (file) => {
  fileName.value = file.name;
  importResult.value = null;
  const reader = new FileReader();
  reader.onload = (e) => {
    fileContent.value = e.target.result;
    toast.info(`Loaded ${file.name} successfully.`);
  };
  reader.readAsText(file);
};

const executeImport = async () => {
  if (!fileContent.value) return;

  importing.value = true;
  importResult.value = null;

  try {
    const res = await store.scryFetch('/import', {
      method: 'POST',
      body: JSON.stringify({
        type: importType.value,
        content: fileContent.value,
        table: targetImportTable.value || null,
      }),
    });

    const data = await res.json();
    importResult.value = data;

    if (data.success) {
      toast.success('Import executed and transaction committed successfully!');
      loadTables();
    } else {
      toast.error('Import failed — transaction rolled back.');
    }
  } catch (err) {
    toast.error('Import request error: ' + err.message);
  } finally {
    importing.value = false;
  }
};

onMounted(loadTables);
watch(() => store.currentConnection, loadTables);
</script>
