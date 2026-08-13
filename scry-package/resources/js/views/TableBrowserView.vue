<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold scry-text-main">Database Tables Browser</h2>
        <p class="text-sm scry-text-muted">Inspect schema, structure, storage size, and row count estimates for connection <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="showCreateModal = true"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm cursor-pointer"
        >
          Create New Table
        </button>
        <button
          @click="loadTables"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg border scry-border scry-bg-card scry-text-main transition-colors shadow-sm cursor-pointer"
        >
          Refresh
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-20 text-center scry-text-muted">
      Loading tables for connection {{ store.currentConnection }}...
    </div>

    <!-- Tables Grid -->
    <div v-else class="scry-bg-card border scry-border rounded-xl overflow-hidden shadow-sm">
      <div class="px-5 py-4 border-b scry-border scry-bg-header flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <span class="font-semibold text-sm scry-text-main">Tables</span>
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
        <table class="w-full text-left text-xs font-mono">
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
                  @click="confirmTruncateTable(t.name)"
                  class="px-2.5 py-1 text-[11px] font-semibold rounded bg-amber-500/15 text-amber-600 dark:text-amber-400 hover:bg-amber-500/25 transition-colors cursor-pointer"
                >
                  Truncate
                </button>
                <button
                  @click="confirmDropTable(t.name)"
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

    <!-- Create Table Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showCreateModal = false">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-2xl w-full shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b scry-border pb-3">
          <h3 class="font-bold text-base scry-text-main">Create New Table</h3>
          <button @click="showCreateModal = false" class="text-xs scry-text-muted font-bold cursor-pointer">Close &times;</button>
        </div>

        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Table Name</label>
          <input v-model="newTableName" type="text" placeholder="e.g. audit_logs" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main" />
        </div>

        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-semibold scry-text-muted">Columns</label>
            <button @click="addColumnRow" class="text-xs font-semibold scry-accent-text cursor-pointer">+ Add Column</button>
          </div>

          <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
            <div v-for="(col, index) in newColumns" :key="index" class="flex items-center space-x-2">
              <input v-model="col.name" type="text" placeholder="Column name" class="w-1/3 scry-bg-input border scry-border rounded p-1.5 text-xs font-mono scry-text-main" />
              <select v-model="col.type" class="w-1/3 scry-bg-input border scry-border rounded p-1.5 text-xs font-mono scry-text-main">
                <option value="INT">INT</option>
                <option value="BIGINT">BIGINT</option>
                <option value="VARCHAR(255)">VARCHAR(255)</option>
                <option value="TEXT">TEXT</option>
                <option value="BOOLEAN">BOOLEAN</option>
                <option value="TIMESTAMP">TIMESTAMP</option>
              </select>
              <label class="flex items-center space-x-1 text-[11px] scry-text-muted cursor-pointer">
                <input type="checkbox" v-model="col.is_primary" />
                <span>PK</span>
              </label>
              <label class="flex items-center space-x-1 text-[11px] scry-text-muted cursor-pointer">
                <input type="checkbox" v-model="col.nullable" />
                <span>Null</span>
              </label>
              <button @click="removeColumnRow(index)" class="text-rose-500 font-bold text-xs cursor-pointer px-1">&times;</button>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-3 border-t scry-border">
          <button @click="showCreateModal = false" class="px-4 py-2 text-xs font-semibold rounded-lg border scry-border scry-text-main">Cancel</button>
          <button @click="submitCreateTable" :disabled="creating" class="px-4 py-2 text-xs font-semibold rounded-lg scry-accent-bg disabled:opacity-50">
            {{ creating ? 'Creating...' : 'Create Table' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Copy Table Modal -->
    <div v-if="showCopyModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showCopyModal = false">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <h3 class="font-bold text-base scry-text-main">Copy Table: {{ activeTable }}</h3>
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Target Table Name</label>
          <input v-model="targetCopyName" type="text" placeholder="e.g. {{ activeTable }}_backup" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main" />
        </div>
        <label class="flex items-center space-x-2 text-xs font-mono scry-text-main cursor-pointer">
          <input type="checkbox" v-model="copyData" />
          <span>Copy Data Rows</span>
        </label>
        <div class="flex items-center justify-end space-x-2 pt-3 border-t scry-border">
          <button @click="showCopyModal = false" class="px-4 py-2 text-xs font-semibold rounded-lg border scry-border scry-text-main">Cancel</button>
          <button @click="submitCopyTable" class="px-4 py-2 text-xs font-semibold rounded-lg scry-accent-bg">Copy Table</button>
        </div>
      </div>
    </div>

    <!-- Rename Table Modal -->
    <div v-if="showRenameModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showRenameModal = false">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <h3 class="font-bold text-base scry-text-main">Rename Table: {{ activeTable }}</h3>
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">New Table Name</label>
          <input v-model="targetRenameName" type="text" placeholder="e.g. new_table_name" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main" />
        </div>
        <div class="flex items-center justify-end space-x-2 pt-3 border-t scry-border">
          <button @click="showRenameModal = false" class="px-4 py-2 text-xs font-semibold rounded-lg border scry-border scry-text-main">Cancel</button>
          <button @click="submitRenameTable" class="px-4 py-2 text-xs font-semibold rounded-lg scry-accent-bg">Rename Table</button>
        </div>
      </div>
    </div>
    <!-- Index Manager Modal -->
    <IndexManagerModal
      :show="showIndexModal"
      :tableName="selectedIndexTable"
      @close="showIndexModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';
import IndexManagerModal from '../components/IndexManagerModal.vue';

const store = useConnectionStore();
const loading = ref(true);
const tables = ref([]);
const searchQuery = ref('');

const selectedIndexTable = ref('');
const showIndexModal = ref(false);

const openIndexModal = (tableName) => {
  selectedIndexTable.value = tableName;
  showIndexModal.value = true;
};

const confirmTruncateTable = async (tableName) => {
  if (!confirm(`Are you sure you want to TRUNCATE table [${tableName}]? All rows will be permanently deleted.`)) return;

  try {
    const res = await store.scryFetch(`/tables/${tableName}/truncate`, { method: 'POST' });
    if (res.ok) {
      await loadTables();
    } else {
      const data = await res.json();
      alert('Truncate Error: ' + (data.error || 'Failed to truncate table.'));
    }
  } catch (err) {
    alert('Truncate Error: ' + err.message);
  }
};

const confirmOptimizeTable = async (tableName) => {
  try {
    const res = await store.scryFetch(`/tables/${tableName}/optimize`, { method: 'POST' });
    if (res.ok) {
      alert(`Table [${tableName}] optimized/vacuumed successfully.`);
      await loadTables();
    } else {
      const data = await res.json();
      alert('Optimize Error: ' + (data.error || 'Failed to optimize table.'));
    }
  } catch (err) {
    alert('Optimize Error: ' + err.message);
  }
};

const showCreateModal = ref(false);
const newTableName = ref('');
const creating = ref(false);
const newColumns = ref([
  { name: 'id', type: 'BIGINT', is_primary: true, nullable: false, auto_increment: true },
  { name: 'title', type: 'VARCHAR(255)', is_primary: false, nullable: false, auto_increment: false },
]);

const showCopyModal = ref(false);
const activeTable = ref('');
const targetCopyName = ref('');
const copyData = ref(true);

const showRenameModal = ref(false);
const targetRenameName = ref('');

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
  } finally {
    loading.value = false;
  }
};

const filteredTables = computed(() => {
  if (!searchQuery.value.trim()) return tables.value;
  const q = searchQuery.value.toLowerCase();
  return tables.value.filter(t => t.name.toLowerCase().includes(q));
});

const addColumnRow = () => {
  newColumns.value.push({ name: '', type: 'VARCHAR(255)', is_primary: false, nullable: true, auto_increment: false });
};

const removeColumnRow = (index) => {
  newColumns.value.splice(index, 1);
};

const submitCreateTable = async () => {
  if (!newTableName.value.trim() || newColumns.value.length === 0) return;
  creating.value = true;

  try {
    const res = await store.scryFetch('/tables', {
      method: 'POST',
      body: JSON.stringify({ name: newTableName.value, columns: newColumns.value }),
    });
    if (res.ok) {
      showCreateModal.value = false;
      newTableName.value = '';
      loadTables();
    }
  } catch (err) {
    alert('Failed to create table: ' + err.message);
  } finally {
    creating.value = false;
  }
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
      loadTables();
    }
  } catch (err) {
    alert('Copy failed: ' + err.message);
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
      loadTables();
    }
  } catch (err) {
    alert('Rename failed: ' + err.message);
  }
};

const confirmDropTable = async (tableName) => {
  if (!confirm(`Are you sure you want to DROP table '${tableName}'? This action cannot be undone.`)) return;

  try {
    const res = await store.scryFetch(`/tables/${tableName}`, { method: 'DELETE' });
    if (res.ok) {
      loadTables();
    }
  } catch (err) {
    alert('Drop failed: ' + err.message);
  }
};

const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    showCreateModal.value = false;
    showCopyModal.value = false;
    showRenameModal.value = false;
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
