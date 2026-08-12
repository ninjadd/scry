<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <div class="mb-6">
      <h2 class="text-2xl font-bold scry-text-main mb-1">Data Import & Multi-Format Export Tools</h2>
      <p class="text-sm scry-text-muted">Import datasets or export schemas into CSV, SQL, XML, PDF, Word, OpenDocument, JSON, and LaTeX formats for <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- File Import Section -->
      <div class="scry-bg-card border scry-border rounded-xl p-6 shadow-sm flex flex-col justify-between">
        <div>
          <h3 class="text-base font-semibold scry-text-main mb-2">Import File (SQL / CSV)</h3>
          <p class="text-xs scry-text-muted mb-4">Upload raw SQL scripts or structured CSV datasets directly into your database tables:</p>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold scry-text-muted mb-1">Import Format</label>
              <select v-model="importType" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main">
                <option value="sql">SQL Script Dump (.sql)</option>
                <option value="csv">CSV Structured File (.csv)</option>
              </select>
            </div>

            <div v-if="importType === 'csv'">
              <label class="block text-xs font-semibold scry-text-muted mb-1">Target Table Name</label>
              <input v-model="targetTable" type="text" placeholder="e.g. users, categories" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main" />
            </div>

            <div>
              <label class="block text-xs font-semibold scry-text-muted mb-1">File Content</label>
              <textarea
                v-model="importContent"
                rows="6"
                placeholder="Paste SQL dump commands or CSV lines here..."
                class="w-full scry-bg-input border scry-border rounded p-3 text-xs font-mono scry-accent-text"
              ></textarea>
            </div>
          </div>
        </div>

        <div class="mt-6 pt-4 border-t scry-border-subtle flex items-center justify-between">
          <div class="text-xs font-mono">
            <span v-if="importMessage" :class="importSuccess ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-rose-600 dark:text-rose-400 font-bold'">
              {{ importMessage }}
            </span>
          </div>

          <button
            @click="submitImport"
            :disabled="importing || !importContent.trim()"
            class="px-5 py-2 text-xs font-semibold rounded-lg scry-accent-bg disabled:opacity-50 transition-colors shadow-sm cursor-pointer"
          >
            {{ importing ? 'Importing...' : 'Run Import' }}
          </button>
        </div>
      </div>

      <!-- Export Formats Overview Section -->
      <div class="scry-bg-card border scry-border rounded-xl p-6 shadow-sm flex flex-col justify-between">
        <div>
          <h3 class="text-base font-semibold scry-text-main mb-2">Export Data Streams</h3>
          <p class="text-xs scry-text-muted mb-4">Select a table to download formatted export payloads:</p>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold scry-text-muted mb-1">Select Table</label>
              <select v-model="exportTable" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main">
                <option value="">-- Choose Table --</option>
                <option v-for="t in availableTables" :key="t.name" :value="t.name">{{ t.name }}</option>
              </select>
            </div>

            <div v-if="exportTable" class="grid grid-cols-2 gap-2 pt-2">
              <a
                v-for="fmt in ['csv', 'sql', 'xml', 'pdf', 'doc', 'odt', 'json', 'latex']"
                :key="fmt"
                :href="`${store.baseApiUrl}/export/${exportTable}?format=${fmt}&connection=${store.currentConnection}`"
                target="_blank"
                download
                class="px-3 py-2 text-xs font-mono font-bold uppercase rounded-lg border scry-border text-center hover:border-pink-600 scry-bg-input scry-text-main transition-colors shadow-sm"
              >
                Download .{{ fmt }}
              </a>
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

const store = useConnectionStore();

const availableTables = ref([]);
const exportTable = ref('');

const importType = ref('sql');
const targetTable = ref('');
const importContent = ref('');
const importing = ref(false);
const importMessage = ref('');
const importSuccess = ref(false);

const loadTables = async () => {
  try {
    const res = await store.scryFetch('/tables');
    if (res.ok) {
      const data = await res.json();
      availableTables.value = data.tables || [];
      if (availableTables.value.length > 0) {
        exportTable.value = availableTables.value[0].name;
      }
    }
  } catch (err) {
    console.error(err);
  }
};

const submitImport = async () => {
  importing.value = true;
  importMessage.value = '';

  try {
    const res = await store.scryFetch('/import', {
      method: 'POST',
      body: JSON.stringify({
        type: importType.value,
        content: importContent.value,
        table: targetTable.value,
      }),
    });

    const data = await res.json();
    if (!res.ok || data.error) {
      throw new Error(data.error || 'Import failed.');
    }

    importSuccess.value = true;
    importMessage.value = importType.value === 'sql'
      ? `Successfully executed ${data.executed_statements} SQL statement(s).`
      : `Successfully inserted ${data.inserted_rows} CSV row(s).`;
    importContent.value = '';
  } catch (err) {
    importSuccess.value = false;
    importMessage.value = err.message;
  } finally {
    importing.value = false;
  }
};

onMounted(loadTables);
watch(() => store.currentConnection, loadTables);
</script>
