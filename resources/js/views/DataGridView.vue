<template>
  <div class="flex-1 flex flex-col overflow-hidden scry-bg-app">
    <!-- Header -->
    <div class="p-6 border-b scry-border scry-bg-header flex flex-col md:flex-row md:items-center justify-between gap-4 select-none">
      <div>
        <div class="flex items-center space-x-3 mb-1">
          <router-link to="/tables" class="text-xs scry-text-muted hover:scry-text-main focus:outline-none focus:ring-1 focus:ring-pink-500/50 rounded px-1">&larr; Back to Tables</router-link>
          <span class="text-xs scry-text-subtle">/</span>
          <span class="text-xs scry-accent-text font-mono font-bold">{{ table }}</span>
        </div>
        <div class="flex items-center space-x-3">
          <h2 class="text-2xl font-bold font-mono scry-text-main">{{ table }}</h2>
          <span class="text-xs px-2 py-0.5 rounded scry-badge-glaucous font-mono font-bold" v-if="meta.total">
            {{ meta.total.toLocaleString() }} rows
          </span>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button
          @click="openCreateDrawer"
          class="px-3.5 py-1.5 text-xs font-semibold rounded-md scry-accent-bg transition-colors shadow-sm cursor-pointer flex items-center space-x-1 focus:outline-none focus:ring-2 focus:ring-pink-500/50"
        >
          <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          <span>Insert New Row</span>
        </button>

        <a
          :href="`${store.baseApiUrl}/export/${table}?format=csv&connection=${store.currentConnection}`"
          target="_blank"
          download
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-glaucous hover:opacity-80 transition-opacity shadow-sm flex items-center space-x-1"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <span>Export CSV</span>
        </a>

        <a
          :href="`${store.baseApiUrl}/export/${table}?format=sql&connection=${store.currentConnection}`"
          target="_blank"
          download
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-sulphur hover:opacity-80 transition-opacity shadow-sm flex items-center space-x-1"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
          </svg>
          <span>Export SQL</span>
        </a>

        <router-link
          :to="{ name: 'schema', params: { table } }"
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-pale-blue transition-colors shadow-sm"
        >
          View Schema
        </router-link>

        <button
          @click="fetchData"
          class="px-3 py-1.5 text-xs font-semibold rounded-md border scry-border scry-bg-card scry-text-main hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shadow-sm cursor-pointer"
        >
          Refresh
        </button>
      </div>
    </div>

    <!-- Data Table Container -->
    <div class="flex-1 overflow-auto p-6">
      <div v-if="loading" class="py-20 text-center scry-text-muted font-mono text-xs">
        Loading table data...
      </div>

      <div v-else-if="rows.length === 0" class="py-20 text-center scry-text-muted font-mono text-xs">
        No records found in table [{{ table }}].
      </div>

      <div v-else class="border scry-border rounded-xl overflow-hidden shadow-lg scry-bg-card">
        <div class="px-4 py-2 bg-pink-500/5 border-b scry-border text-[11px] scry-text-muted flex items-center justify-between font-mono">
          <span>💡 Click any row to open the side edit drawer.</span>
          <span>Click column header to sort</span>
        </div>

        <table class="w-full text-left text-xs font-mono select-none">
          <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider sticky top-0">
            <tr>
              <th
                v-for="col in columns"
                :key="col"
                @click="sort(col)"
                class="px-4 py-3 cursor-pointer hover:scry-text-main whitespace-nowrap"
              >
                <div class="flex items-center space-x-1">
                  <span :class="{ 'scry-accent-text font-bold': sortBy === col }">{{ col }}</span>
                  <span v-if="sortBy === col" class="scry-accent-text font-bold">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
              <th class="px-4 py-3 text-right">Edit</th>
            </tr>
          </thead>
          <tbody class="divide-y scry-border-subtle scry-text-main">
            <tr
              v-for="(row, i) in rows"
              :key="i"
              @click="openEditDrawer(row)"
              class="hover:bg-pink-500/10 dark:hover:bg-pink-500/15 cursor-pointer transition-colors group"
              title="Click to edit row in side drawer"
            >
              <td
                v-for="col in columns"
                :key="col"
                class="px-4 py-2.5 max-w-xs truncate"
                :title="row[col]"
              >
                <span v-if="row[col] === null" class="scry-text-subtle italic">null</span>
                <span v-else-if="typeof row[col] === 'boolean'" class="px-1.5 py-0.5 rounded text-[10px] font-bold scry-badge-glaucous">{{ row[col] }}</span>
                <span v-else-if="typeof row[col] === 'number'" class="px-1.5 py-0.5 rounded text-[10px] font-bold scry-badge-sulphur">{{ row[col] }}</span>
                <span v-else>{{ row[col] }}</span>
              </td>
              <td class="px-4 py-2.5 text-right whitespace-nowrap">
                <span class="text-[11px] font-semibold text-pink-600 dark:text-pink-400 group-hover:underline">
                  Edit &rarr;
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination Footer -->
    <div v-if="meta && meta.total !== undefined" class="p-4 border-t scry-border scry-bg-header flex flex-col sm:flex-row items-center justify-between gap-3 text-xs scry-text-muted font-mono">
      <div class="flex items-center space-x-3">
        <div>
          Showing page <span class="scry-text-main font-bold">{{ meta.page || meta.current_page || 1 }}</span> of <span class="scry-text-main font-bold">{{ meta.last_page || 1 }}</span> ({{ (meta.total || 0).toLocaleString() }} records total)
        </div>
        <div class="flex items-center space-x-1.5 border-l scry-border pl-3">
          <span class="text-[11px] scry-text-subtle">Per page:</span>
          <select
            v-model="perPage"
            @change="handlePerPageChange"
            class="scry-bg-input border scry-border rounded px-2 py-1 text-xs scry-text-main focus:outline-none focus:ring-1 focus:ring-pink-500/50"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>

      <div class="flex space-x-2">
        <button
          :disabled="(meta.page || meta.current_page || 1) <= 1"
          @click="changePage((meta.page || meta.current_page || 1) - 1)"
          class="px-3 py-1.5 rounded scry-bg-card border scry-border disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-800 scry-text-main font-medium cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500/50"
        >
          Previous
        </button>
        <button
          :disabled="(meta.page || meta.current_page || 1) >= (meta.last_page || 1)"
          @click="changePage((meta.page || meta.current_page || 1) + 1)"
          class="px-3 py-1.5 rounded scry-bg-card border scry-border disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-800 scry-text-main font-medium cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500/50"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Right Side Edit / Create Row Drawer -->
    <div v-if="showDrawer" class="fixed inset-0 z-40 overflow-hidden">
      <!-- Backdrop Overlay -->
      <div
        class="absolute inset-0 bg-black/50 backdrop-blur-xs transition-opacity"
        @click="closeDrawer"
      ></div>

      <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div class="w-screen max-w-md scry-bg-card border-l scry-border shadow-2xl flex flex-col justify-between">
          <!-- Drawer Header -->
          <div class="p-5 border-b scry-border scry-bg-header flex items-center justify-between">
            <div>
              <div class="flex items-center space-x-2">
                <span class="text-xs uppercase font-bold tracking-wider scry-accent-text">
                  {{ drawerMode === 'edit' ? 'Edit Row' : 'Insert New Row' }}
                </span>
                <span class="text-xs font-mono px-2 py-0.5 rounded scry-badge-pale-blue">
                  {{ table }}
                </span>
              </div>
              <p class="text-xs scry-text-muted mt-1 font-mono">
                <span v-if="drawerMode === 'edit'">Primary Key: <strong class="scry-text-main">{{ primaryKeyDisplay }}</strong></span>
                <span v-else>Fill field inputs to insert row.</span>
              </p>
            </div>

            <button
              @click="closeDrawer"
              class="p-1.5 rounded-lg border scry-border text-xs font-bold scry-text-muted hover:scry-text-main hover:scry-bg-card cursor-pointer"
              title="Close Drawer (Esc)"
            >
              ✕
            </button>
          </div>

          <!-- Drawer Form Body -->
          <div class="flex-1 overflow-y-auto p-5 space-y-4 font-mono text-xs">
            <div
              v-for="col in (schemaColumns.length > 0 ? schemaColumns : columns.map(c => ({ name: c, is_primary: c === 'id' })))"
              :key="col.name"
              class="p-3 border scry-border rounded-lg scry-bg-input space-y-1.5"
            >
              <div class="flex items-center justify-between">
                <label class="font-bold scry-text-main flex items-center space-x-1.5">
                  <span>{{ col.name }}</span>
                  <span v-if="col.is_primary" class="text-[9px] font-sans font-bold px-1.5 py-0.2 rounded scry-badge-sulphur">PK</span>
                  <span v-if="col.auto_increment" class="text-[9px] font-sans font-bold px-1.5 py-0.2 rounded scry-badge-glaucous">AI</span>
                </label>

                <div v-if="col.nullable && !col.is_primary" class="flex items-center space-x-1 text-[11px] scry-text-muted">
                  <input
                    type="checkbox"
                    :id="`null-${col.name}`"
                    v-model="nullToggles[col.name]"
                    class="rounded scry-border accent-pink-600 cursor-pointer"
                  />
                  <label :for="`null-${col.name}`" class="cursor-pointer">Set NULL</label>
                </div>
              </div>

              <!-- Input Controls -->
              <div v-if="nullToggles[col.name]" class="py-1.5 px-3 bg-slate-500/10 rounded text-xs italic text-slate-400 font-mono">
                VALUE IS NULL
              </div>

              <div v-else>
                <!-- JSON Formatted Editor -->
                <div v-if="isJsonColumn(col)">
                  <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-pink-600 dark:text-pink-400 flex items-center space-x-1">
                      <span>JSON Object / Array</span>
                    </span>
                    <button
                      type="button"
                      @click="formatJsonField(col.name)"
                      class="text-[10px] font-bold scry-badge-pale-blue px-2 py-0.5 rounded cursor-pointer hover:opacity-80 transition-opacity"
                    >
                      Format JSON
                    </button>
                  </div>
                  <textarea
                    v-model="editFormData[col.name]"
                    rows="6"
                    :disabled="drawerMode === 'edit' && col.is_primary && col.auto_increment"
                    class="w-full scry-bg-input border scry-border rounded-lg p-3 text-xs font-mono scry-accent-text focus:outline-none focus:ring-2 focus:ring-pink-500/50 shadow-inner"
                    placeholder="{ &quot;key&quot;: &quot;value&quot; }"
                  ></textarea>
                </div>

                <!-- Textarea for long text -->
                <textarea
                  v-else-if="col.data_type && (col.data_type.includes('text') || col.name === 'content' || col.name === 'description')"
                  v-model="editFormData[col.name]"
                  rows="4"
                  :disabled="drawerMode === 'edit' && col.is_primary && col.auto_increment"
                  class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main focus:outline-none focus:ring-2 focus:ring-pink-500/50 disabled:opacity-50"
                  :placeholder="`Enter ${col.name}...`"
                ></textarea>

                <!-- Regular Input -->
                <input
                  v-else
                  v-model="editFormData[col.name]"
                  :type="col.data_type && (col.data_type.includes('int') || col.data_type.includes('decimal')) ? 'number' : 'text'"
                  :disabled="drawerMode === 'edit' && col.is_primary && col.auto_increment"
                  class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main focus:outline-none focus:ring-2 focus:ring-pink-500/50 disabled:opacity-50"
                  :placeholder="`Enter ${col.name}...`"
                />
              </div>

              <div class="flex items-center justify-between text-[10px] scry-text-subtle">
                <span>{{ col.full_type || col.data_type || 'field' }}</span>
                <span v-if="col.is_primary && col.auto_increment" class="italic">Auto-generated</span>
              </div>
            </div>
          </div>

          <!-- Drawer Action Footer -->
          <div class="p-4 border-t scry-border scry-bg-header flex items-center justify-between">
            <div>
              <button
                v-if="drawerMode === 'edit'"
                @click="promptConfirm('delete')"
                class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-rose-500/15 text-rose-600 dark:text-rose-400 hover:bg-rose-500/25 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-rose-500/50"
              >
                Delete Row
              </button>
            </div>

            <div class="flex space-x-2">
              <button
                @click="closeDrawer"
                class="px-4 py-2 text-xs font-semibold rounded-lg border scry-border scry-text-main hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
              >
                Cancel
              </button>

              <button
                @click="promptConfirm(drawerMode === 'edit' ? 'update' : 'insert')"
                class="px-5 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500/50"
              >
                {{ drawerMode === 'edit' ? 'Save Changes' : 'Insert Row' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal Dialog -->
    <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 select-none">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex items-center space-x-3">
          <div
            class="p-2.5 rounded-full"
            :class="confirmActionType === 'delete' ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400' : 'scry-badge-glaucous'"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="confirmActionType === 'delete'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <div>
            <h3 class="font-bold text-base scry-text-main">
              {{ confirmActionType === 'delete' ? 'Confirm Delete Row' : (confirmActionType === 'update' ? 'Confirm Save Changes' : 'Confirm Insert Row') }}
            </h3>
            <p class="text-xs scry-text-muted mt-0.5">
              Table: <strong class="font-mono scry-accent-text">{{ table }}</strong>
            </p>
          </div>
        </div>

        <div class="p-3 rounded-lg scry-bg-input border scry-border text-xs scry-text-main leading-relaxed font-mono">
          <span v-if="confirmActionType === 'delete'">
            Are you sure you want to permanently <strong class="text-rose-500 uppercase">delete</strong> row with Primary Key <strong class="scry-accent-text">{{ primaryKeyDisplay }}</strong>?
          </span>
          <span v-else-if="confirmActionType === 'update'">
            Are you sure you want to <strong class="scry-accent-text uppercase">update</strong> row fields in connection [{{ store.currentConnection }}]?
          </span>
          <span v-else>
            Are you sure you want to <strong class="scry-accent-text uppercase">insert</strong> a new record into table [{{ table }}]?
          </span>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-2 border-t scry-border">
          <button
            @click="showConfirmModal = false"
            class="px-4 py-2 text-xs font-semibold rounded-lg border scry-border scry-text-main hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          >
            Cancel
          </button>

          <button
            @click="executeConfirmedAction"
            :disabled="isSubmitting"
            class="px-5 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer shadow-sm focus:outline-none focus:ring-2 focus:ring-pink-500/50"
            :class="confirmActionType === 'delete' ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'scry-accent-bg'"
          >
            {{ isSubmitting ? 'Processing...' : (confirmActionType === 'delete' ? 'Yes, Delete Row' : 'Yes, Confirm') }}
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

const props = defineProps({ table: String });
const store = useConnectionStore();
const toast = useToastStore();

const rows = ref([]);
const meta = ref({});
const loading = ref(true);
const page = ref(1);
const perPage = ref(25);
const sortBy = ref(null);
const sortDir = ref('asc');

// Drawer & Modal State
const showDrawer = ref(false);
const drawerMode = ref('edit'); // 'edit' or 'create'
const selectedRow = ref(null);
const editFormData = ref({});
const nullToggles = ref({});
const schemaColumns = ref([]);
const primaryKeyCol = ref('id');

const showConfirmModal = ref(false);
const confirmActionType = ref(''); // 'update', 'delete', 'insert'
const isSubmitting = ref(false);

const columns = computed(() => {
  if (rows.value.length === 0) return [];
  return Object.keys(rows.value[0]);
});

const primaryKeyDisplay = computed(() => {
  if (!selectedRow.value) return 'N/A';
  if (selectedRow.value[primaryKeyCol.value] !== undefined) {
    return `${primaryKeyCol.value}=${selectedRow.value[primaryKeyCol.value]}`;
  }
  const firstCol = columns.value[0];
  return firstCol ? `${firstCol}=${selectedRow.value[firstCol]}` : 'N/A';
});

const getPrimaryKeyCondition = () => {
  if (!selectedRow.value) return {};
  if (selectedRow.value[primaryKeyCol.value] !== undefined) {
    return { [primaryKeyCol.value]: selectedRow.value[primaryKeyCol.value] };
  }
  const firstCol = columns.value[0];
  return firstCol ? { [firstCol]: selectedRow.value[firstCol] } : {};
};

const fetchSchema = async () => {
  try {
    const res = await store.scryFetch(`/tables/${props.table}/schema`);
    if (res.ok) {
      const data = await res.json();
      schemaColumns.value = data.columns || [];
      const pk = schemaColumns.value.find(c => c.is_primary);
      if (pk) {
        primaryKeyCol.value = pk.name;
      }
    }
  } catch (err) {
    console.error('Failed to fetch schema for drawer:', err);
  }
};

const fetchData = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      page: page.value,
      per_page: perPage.value,
      ...(sortBy.value && { sort_by: sortBy.value, sort_dir: sortDir.value }),
    });

    const res = await store.scryFetch(`/tables/${props.table}/rows?${params}`);
    if (res.ok) {
      const data = await res.json();
      rows.value = data.data || [];
      meta.value = data.meta || {
        page: data.current_page || data.page || 1,
        current_page: data.current_page || data.page || 1,
        per_page: data.per_page || 25,
        total: data.total ?? 0,
        last_page: data.last_page || 1,
      };
    }
  } catch (err) {
    console.error('Failed to load table data:', err);
  } finally {
    loading.value = false;
  }
};

const formatJsonIfValid = (val) => {
  if (val === null || val === undefined) return '';
  if (typeof val === 'object') {
    try {
      return JSON.stringify(val, null, 2);
    } catch (e) {
      return String(val);
    }
  }
  if (typeof val === 'string') {
    const trimmed = val.trim();
    if ((trimmed.startsWith('{') && trimmed.endsWith('}')) || (trimmed.startsWith('[') && trimmed.endsWith(']'))) {
      try {
        const parsed = JSON.parse(trimmed);
        return JSON.stringify(parsed, null, 2);
      } catch (e) {
        return val;
      }
    }
  }
  return val;
};

const isJsonColumn = (col) => {
  if (!col) return false;
  const colType = (col.data_type || '').toLowerCase();
  if (colType.includes('json') || colType.includes('array')) {
    return true;
  }
  const val = editFormData.value[col.name];
  if (typeof val === 'string') {
    const trimmed = val.trim();
    if ((trimmed.startsWith('{') && trimmed.endsWith('}')) || (trimmed.startsWith('[') && trimmed.endsWith(']'))) {
      return true;
    }
  }
  return false;
};

const formatJsonField = (colName) => {
  const currentVal = editFormData.value[colName];
  if (typeof currentVal === 'string') {
    try {
      const parsed = JSON.parse(currentVal);
      editFormData.value[colName] = JSON.stringify(parsed, null, 2);
    } catch (err) {
      alert('Invalid JSON syntax: ' + err.message);
    }
  }
};

const openEditDrawer = (row) => {
  drawerMode.value = 'edit';
  selectedRow.value = row;
  editFormData.value = {};
  nullToggles.value = {};

  for (const k in row) {
    nullToggles.value[k] = (row[k] === null);
    if (row[k] !== null) {
      editFormData.value[k] = formatJsonIfValid(row[k]);
    } else {
      editFormData.value[k] = '';
    }
  }
  showDrawer.value = true;
};

const openCreateDrawer = () => {
  drawerMode.value = 'create';
  selectedRow.value = null;
  editFormData.value = {};
  nullToggles.value = {};

  const cols = schemaColumns.value.length > 0 ? schemaColumns.value.map(c => c.name) : columns.value;
  for (const c of cols) {
    editFormData.value[c] = '';
    nullToggles.value[c] = false;
  }
  showDrawer.value = true;
};

const closeDrawer = () => {
  showDrawer.value = false;
};

const promptConfirm = (actionType) => {
  confirmActionType.value = actionType;
  showConfirmModal.value = true;
};

const executeConfirmedAction = async () => {
  isSubmitting.value = true;

  try {
    // Construct payload data considering NULL toggles
    const payloadData = {};
    for (const k in editFormData.value) {
      if (nullToggles.value[k]) {
        payloadData[k] = null;
      } else {
        payloadData[k] = editFormData.value[k];
      }
    }

    if (confirmActionType.value === 'update') {
      const res = await store.scryFetch(`/tables/${props.table}/rows`, {
        method: 'PUT',
        body: JSON.stringify({
          primary_key: getPrimaryKeyCondition(),
          data: payloadData,
        }),
      });
      if (!res.ok) {
        const errData = await res.json();
        throw new Error(errData.error || 'Failed to update row.');
      }
    } else if (confirmActionType.value === 'delete') {
      const res = await store.scryFetch(`/tables/${props.table}/rows`, {
        method: 'DELETE',
        body: JSON.stringify({
          primary_key: getPrimaryKeyCondition(),
        }),
      });
      if (!res.ok) {
        const errData = await res.json();
        throw new Error(errData.error || 'Failed to delete row.');
      }
    } else if (confirmActionType.value === 'insert') {
      const res = await store.scryFetch(`/tables/${props.table}/rows`, {
        method: 'POST',
        body: JSON.stringify({
          data: payloadData,
        }),
      });
      if (!res.ok) {
        const errData = await res.json();
        throw new Error(errData.error || 'Failed to insert row.');
      }
    }

    const actionName = confirmActionType.value;
    showConfirmModal.value = false;
    showDrawer.value = false;
    await fetchData();
    toast.success(`Row ${actionName}d successfully in [${props.table}].`);
  } catch (err) {
    toast.error(`Action Error (${confirmActionType.value}): ${err.message}`);
  } finally {
    isSubmitting.value = false;
  }
};

const handlePerPageChange = () => {
  page.value = 1;
  fetchData();
};

const changePage = (newPage) => {
  page.value = newPage;
  fetchData();
};

const sort = (col) => {
  if (sortBy.value === col) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = col;
    sortDir.value = 'asc';
  }
  fetchData();
};

const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    if (showConfirmModal.value) {
      showConfirmModal.value = false;
    } else if (showDrawer.value) {
      showDrawer.value = false;
    }
  }
};

onMounted(() => {
  fetchData();
  fetchSchema();
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});

watch(() => props.table, () => {
  page.value = 1;
  sortBy.value = null;
  fetchData();
  fetchSchema();
});

watch(() => store.currentConnection, () => {
  page.value = 1;
  fetchData();
  fetchSchema();
});
</script>
