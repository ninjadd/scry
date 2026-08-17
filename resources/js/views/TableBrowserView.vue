<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 select-none">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Database Tables & Schema Browser</h2>
        <p class="text-sm scry-text-muted">
          Inspect schemas, indexes, foreign keys, storage sizes, and row estimates for connection 
          <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.
        </p>
      </div>

      <div class="flex items-center space-x-2">
        <router-link
          to="/tables/create"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm cursor-pointer"
        >
          + Visual Table Designer
        </router-link>
        <button
          @click="loadTables"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg border scry-border scry-bg-card scry-text-main transition-colors shadow-sm cursor-pointer"
        >
          Refresh
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-20 text-center scry-text-muted font-mono text-xs">
      Loading table metadata for connection {{ store.currentConnection }}...
    </div>

    <!-- Tables Grid -->
    <div v-else class="scry-bg-card border scry-border rounded-xl overflow-hidden shadow-sm">
      <div class="px-5 py-4 border-b scry-border scry-bg-header flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="flex items-center space-x-2">
          <span class="font-semibold text-sm scry-text-main">Database Tables</span>
          <span class="text-xs px-2 py-0.5 rounded scry-badge-glaucous font-mono font-bold">{{ tables.length }}</span>
        </div>

        <input
          v-model="searchQuery"
          type="text"
          placeholder="Filter tables..."
          class="scry-bg-input border scry-border rounded px-3 py-1.5 text-xs scry-text-main focus:outline-none focus:border-pink-600 font-mono"
        />
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs font-mono select-none">
          <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3">Table Name</th>
              <th class="px-5 py-3">Storage Size</th>
              <th class="px-5 py-3">Est. Rows</th>
              <th class="px-5 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y scry-border-subtle scry-text-main">
            <tr v-for="t in filteredTables" :key="t.name" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <td class="px-5 py-3 font-semibold scry-accent-text">
                <router-link :to="{ name: 'data', params: { table: t.name } }" class="hover:underline">
                  {{ t.name }}
                </router-link>
              </td>
              <td class="px-5 py-3 scry-text-main">{{ t.size }}</td>
              <td class="px-5 py-3 scry-text-main">{{ t.rows.toLocaleString() }}</td>
              <td class="px-5 py-3 text-right space-x-1.5 flex flex-wrap justify-end gap-1">
                <router-link
                  :to="{ name: 'data', params: { table: t.name } }"
                  class="px-2.5 py-1 text-[11px] font-semibold rounded scry-badge-pale-blue transition-colors"
                >
                  Browse Data
                </router-link>
                <button
                  @click="openIndexModal(t.name)"
                  class="px-2.5 py-1 text-[11px] font-semibold rounded scry-badge-pale-blue hover:opacity-80 transition-opacity cursor-pointer"
                >
                  Indexes
                </button>
                <button
                  @click="openForeignKeyModal(t.name)"
                  class="px-2.5 py-1 text-[11px] font-semibold rounded scry-badge-pale-blue hover:opacity-80 transition-opacity cursor-pointer"
                >
                  Foreign Keys
                </button>
                <button
                  @click="confirmOptimizeTable(t.name)"
                  class="px-2.5 py-1 text-[11px] font-semibold rounded scry-badge-glaucous hover:opacity-80 transition-opacity cursor-pointer"
                >
                  Optimize
                </button>
                <button
                  @click="openCopyModal(t.name)"
                  class="px-2.5 py-1 text-[11px] font-semibold rounded scry-badge-glaucous transition-colors cursor-pointer"
                >
                  Copy
                </button>
                <button
                  @click="openRenameModal(t.name)"
                  class="px-2.5 py-1 text-[11px] font-semibold rounded scry-badge-sulphur transition-colors cursor-pointer"
                >
                  Rename
                </button>
                <button
                  @click="openDangerModal('truncate', t.name)"
                  class="px-2.5 py-1 text-[11px] font-semibold rounded bg-amber-500/15 text-amber-600 dark:text-amber-400 hover:bg-amber-500/25 transition-colors cursor-pointer"
                >
                  Truncate
                </button>
                <button
                  @click="openDangerModal('drop', t.name)"
                  class="px-2.5 py-1 text-[11px] font-semibold rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 hover:bg-rose-500/25 transition-colors cursor-pointer"
                >
                  Drop
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Index Manager Modal Component -->
    <IndexManagerModal
      :show="showIndexModal"
      :tableName="activeTable"
      @close="showIndexModal = false"
    />

    <!-- Foreign Key Manager Modal Component -->
    <ForeignKeyManagerModal
      :show="showFkModal"
      :tableName="activeTable"
      @close="showFkModal = false"
    />

    <!-- Copy Table Modal -->
    <div v-if="showCopyModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 font-mono select-none">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <h3 class="font-bold text-sm scry-text-main">Copy Table: {{ activeTable }}</h3>
        <div>
          <label class="block text-xs scry-text-muted mb-1 font-bold">New Table Name</label>
          <input
            v-model="targetCopyName"
            type="text"
            class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main focus:outline-none"
          />
        </div>
        <label class="flex items-center space-x-2 cursor-pointer text-xs">
          <input type="checkbox" v-model="copyData" class="rounded text-pink-600" />
          <span class="scry-text-main">Copy Structure & All Rows</span>
        </label>
        <div class="flex items-center justify-end space-x-2 pt-2 border-t scry-border">
          <button @click="showCopyModal = false" class="px-3.5 py-1.5 text-xs rounded border scry-border scry-text-main">Cancel</button>
          <button @click="submitCopyTable" class="px-4 py-1.5 text-xs font-bold rounded scry-accent-bg cursor-pointer">Execute Copy</button>
        </div>
      </div>
    </div>

    <!-- Rename Table Modal -->
    <div v-if="showRenameModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 font-mono select-none">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <h3 class="font-bold text-sm scry-text-main">Rename Table: {{ activeTable }}</h3>
        <div>
          <label class="block text-xs scry-text-muted mb-1 font-bold">New Table Name</label>
          <input
            v-model="targetRenameName"
            type="text"
            class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main focus:outline-none"
          />
        </div>
        <div class="flex items-center justify-end space-x-2 pt-2 border-t scry-border">
          <button @click="showRenameModal = false" class="px-3.5 py-1.5 text-xs rounded border scry-border scry-text-main">Cancel</button>
          <button @click="submitRenameTable" class="px-4 py-1.5 text-xs font-bold rounded scry-accent-bg cursor-pointer">Execute Rename</button>
        </div>
      </div>
    </div>

    <!-- Safe Drop & Truncate Typed Confirmation Modal (Prompt 5.3) -->
    <div v-if="showDangerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 font-mono select-none">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-lg w-full shadow-2xl space-y-4">
        <div class="flex items-center space-x-3">
          <div class="p-3 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
          <div>
            <h3 class="font-bold text-base scry-text-main">
              {{ dangerAction === 'drop' ? 'DROP TABLE' : 'TRUNCATE TABLE' }}: {{ activeTable }}
            </h3>
            <p class="text-xs scry-text-muted mt-0.5">High-Risk Irreversible DDL Operation</p>
          </div>
        </div>

        <div class="p-3 rounded-lg bg-rose-500/10 border border-rose-500/20 text-xs text-rose-500 space-y-2">
          <p v-if="dangerAction === 'drop'">
            This will permanently delete table <strong class="font-bold underline">{{ activeTable }}</strong>, its schema definition, foreign key references, and all associated rows.
          </p>
          <p v-else>
            This will wipe all existing data records from table <strong class="font-bold underline">{{ activeTable }}</strong> while retaining its schema structure.
          </p>
        </div>

        <div>
          <label class="block text-xs scry-text-main mb-1.5">
            To confirm this operation, type <strong class="scry-accent-text font-bold">{{ activeTable }}</strong> below:
          </label>
          <input
            v-model="typedConfirmationName"
            type="text"
            :placeholder="activeTable"
            class="w-full scry-bg-input border scry-border rounded-lg px-3 py-2 text-xs font-mono scry-text-main focus:outline-none focus:ring-2 focus:ring-rose-500"
          />
        </div>

        <!-- Constraint Violation Error Box -->
        <div v-if="dangerError" class="p-3 rounded-lg bg-rose-500/15 text-rose-500 text-xs break-all">
          <strong>Database Constraint Error:</strong> {{ dangerError }}
        </div>

        <div class="flex items-center justify-end space-x-2 pt-2 border-t scry-border">
          <button
            @click="showDangerModal = false"
            class="px-4 py-2 text-xs rounded-lg border scry-border scry-text-main hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="executeDangerAction"
            :disabled="typedConfirmationName !== activeTable || isDangerExecuting"
            class="px-5 py-2 text-xs font-bold rounded-lg bg-rose-600 hover:bg-rose-700 text-white disabled:opacity-40 transition-colors cursor-pointer shadow-sm"
          >
            {{ isDangerExecuting ? 'Executing...' : `Confirm ${dangerAction.toUpperCase()}` }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';
import { useToastStore } from '../stores/useToastStore';
import IndexManagerModal from '../components/IndexManagerModal.vue';
import ForeignKeyManagerModal from '../components/ForeignKeyManagerModal.vue';

const store = useConnectionStore();
const toast = useToastStore();

const loading = ref(false);
const tables = ref([]);
const searchQuery = ref('');

const activeTable = ref('');
const showIndexModal = ref(false);
const showFkModal = ref(false);
const showCopyModal = ref(false);
const targetCopyName = ref('');
const copyData = ref(true);

const showRenameModal = ref(false);
const targetRenameName = ref('');

// Danger Modal State
const showDangerModal = ref(false);
const dangerAction = ref('drop'); // 'drop' | 'truncate'
const typedConfirmationName = ref('');
const isDangerExecuting = ref(false);
const dangerError = ref('');

const filteredTables = computed(() => {
  if (!searchQuery.value.trim()) return tables.value;
  const q = searchQuery.value.toLowerCase();
  return tables.value.filter(t => t.name.toLowerCase().includes(q));
});

const loadTables = async () => {
  loading.value = true;
  try {
    const res = await store.scryFetch('/tables');
    if (res.ok) {
      const data = await res.json();
      tables.value = data.tables || [];
    }
  } catch (err) {
    console.error(err);
    toast.error('Failed to load tables list.');
  } finally {
    loading.value = false;
  }
};

const openIndexModal = (tableName) => {
  activeTable.value = tableName;
  showIndexModal.value = true;
};

const openForeignKeyModal = (tableName) => {
  activeTable.value = tableName;
  showFkModal.value = true;
};

const openCopyModal = (tableName) => {
  activeTable.value = tableName;
  targetCopyName.value = `${tableName}_copy`;
  showCopyModal.value = true;
};

const submitCopyTable = async () => {
  try {
    const res = await store.scryFetch('/tables/copy', {
      method: 'POST',
      body: JSON.stringify({ source_table: activeTable.value, target_table: targetCopyName.value, copy_data: copyData.value }),
    });
    if (res.ok) {
      showCopyModal.value = false;
      toast.success(`Table "${activeTable.value}" copied to "${targetCopyName.value}".`);
      loadTables();
    } else {
      const data = await res.json();
      toast.error('Copy failed: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    toast.error('Copy error: ' + err.message);
  }
};

const openRenameModal = (tableName) => {
  activeTable.value = tableName;
  targetRenameName.value = `${tableName}_renamed`;
  showRenameModal.value = true;
};

const submitRenameTable = async () => {
  try {
    const res = await store.scryFetch(`/tables/${activeTable.value}/rename`, {
      method: 'PUT',
      body: JSON.stringify({ new_name: targetRenameName.value }),
    });
    if (res.ok) {
      showRenameModal.value = false;
      toast.success(`Table "${activeTable.value}" renamed.`);
      loadTables();
    } else {
      const data = await res.json();
      toast.error('Rename failed: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    toast.error('Rename error: ' + err.message);
  }
};

const confirmOptimizeTable = async (tableName) => {
  try {
    const res = await store.scryFetch(`/tables/${tableName}/optimize`, { method: 'POST' });
    if (res.ok) {
      toast.success(`Table "${tableName}" optimized successfully.`);
    } else {
      const data = await res.json();
      toast.error('Optimization error: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    toast.error('Optimize error: ' + err.message);
  }
};

const openDangerModal = (action, tableName) => {
  dangerAction.value = action;
  activeTable.value = tableName;
  typedConfirmationName.value = '';
  dangerError.value = '';
  showDangerModal.value = true;
};

const executeDangerAction = async () => {
  if (typedConfirmationName.value !== activeTable.value) return;

  isDangerExecuting.value = true;
  dangerError.value = '';

  try {
    const res = dangerAction.value === 'drop'
      ? await store.scryFetch(`/tables/${activeTable.value}`, { method: 'DELETE' })
      : await store.scryFetch(`/tables/${activeTable.value}/truncate`, { method: 'POST' });

    const data = await res.json();

    if (res.ok && data.success) {
      toast.success(`Table "${activeTable.value}" ${dangerAction.value === 'drop' ? 'dropped' : 'truncated'} successfully.`);
      showDangerModal.value = false;
      await loadTables();
    } else {
      dangerError.value = data.error || 'Operation failed due to database constraint.';
    }
  } catch (err) {
    dangerError.value = err.message;
  } finally {
    isDangerExecuting.value = false;
  }
};

const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    showIndexModal.value = false;
    showFkModal.value = false;
    showCopyModal.value = false;
    showRenameModal.value = false;
    showDangerModal.value = false;
  }
};

onMounted(() => {
  loadTables();
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});

watch(() => store.currentConnection, loadTables);
</script>
