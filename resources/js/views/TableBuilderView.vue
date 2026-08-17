<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 select-none">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Visual Table Designer (DDL Blueprint)</h2>
        <p class="text-sm scry-text-muted">
          Design, configure columns, indexes, and generate driver-native schema blueprints for 
          <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.
        </p>
      </div>

      <div class="flex items-center space-x-2">
        <router-link
          to="/tables"
          class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border scry-border scry-bg-card scry-text-main hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          &larr; Back to Tables
        </router-link>
        <button
          @click="createTable"
          :disabled="creating || !tableName.trim() || columns.length === 0"
          class="px-5 py-1.5 text-xs font-semibold rounded-lg scry-accent-bg disabled:opacity-50 transition-colors cursor-pointer shadow-sm"
        >
          <span>{{ creating ? 'Creating Table...' : 'Create Table Blueprint' }}</span>
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 font-mono">
      <!-- Left 2 Cols: Table & Columns Designer -->
      <div class="lg:col-span-2 space-y-4">
        <!-- Table Metadata Card -->
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm space-y-3">
          <label class="block text-xs font-bold uppercase tracking-wider scry-text-subtle">Table Name</label>
          <input
            v-model="tableName"
            type="text"
            placeholder="e.g. products, order_items, users"
            class="w-full scry-bg-input border scry-border rounded-lg px-3.5 py-2 text-sm scry-text-main font-bold focus:outline-none focus:ring-2 focus:ring-pink-500"
          />
        </div>

        <!-- Columns Designer Card -->
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b scry-border pb-3">
            <h3 class="font-bold text-sm uppercase tracking-wider scry-text-main">Column Definitions ({{ columns.length }})</h3>

            <!-- Quick Presets -->
            <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
              <span class="text-slate-400">Add Presets:</span>
              <button
                @click="addPresetId"
                class="px-2 py-0.5 rounded bg-pink-500/10 text-pink-500 hover:bg-pink-500/20 transition-colors cursor-pointer"
              >
                + id (PK)
              </button>
              <button
                @click="addPresetTimestamps"
                class="px-2 py-0.5 rounded bg-sky-500/10 text-sky-500 hover:bg-sky-500/20 transition-colors cursor-pointer"
              >
                + timestamps
              </button>
              <button
                @click="addPresetUuid"
                class="px-2 py-0.5 rounded bg-purple-500/10 text-purple-500 hover:bg-purple-500/20 transition-colors cursor-pointer"
              >
                + uuid
              </button>
              <button
                @click="addColumn"
                class="px-2.5 py-0.5 rounded scry-accent-bg font-bold cursor-pointer"
              >
                + New Column
              </button>
            </div>
          </div>

          <!-- Column Rows -->
          <div class="space-y-2.5 max-h-[500px] overflow-y-auto pr-1">
            <div
              v-for="(col, index) in columns"
              :key="index"
              class="p-3 rounded-lg border scry-border scry-bg-input flex flex-col md:flex-row items-start md:items-center gap-2 text-xs"
            >
              <div class="flex items-center space-x-1 w-full md:w-auto">
                <span class="text-[10px] text-slate-400 w-4">{{ index + 1 }}.</span>
                <input
                  v-model="col.name"
                  type="text"
                  placeholder="column_name"
                  class="flex-1 md:w-36 scry-bg-card border scry-border rounded px-2.5 py-1.5 scry-text-main focus:outline-none focus:ring-1 focus:ring-pink-500 font-bold"
                />
              </div>

              <!-- Type Selector -->
              <select
                v-model="col.type"
                class="scry-bg-card border scry-border rounded px-2 py-1.5 scry-text-main focus:outline-none"
              >
                <option value="BIGINT">BIGINT</option>
                <option value="INTEGER">INTEGER</option>
                <option value="SMALLINT">SMALLINT</option>
                <option value="VARCHAR(255)">VARCHAR(255)</option>
                <option value="VARCHAR(100)">VARCHAR(100)</option>
                <option value="TEXT">TEXT</option>
                <option value="LONGTEXT">LONGTEXT</option>
                <option value="BOOLEAN">BOOLEAN</option>
                <option value="DECIMAL(10,2)">DECIMAL(10,2)</option>
                <option value="FLOAT">FLOAT</option>
                <option value="DATETIME">DATETIME</option>
                <option value="TIMESTAMP">TIMESTAMP</option>
                <option value="JSON">JSON</option>
                <option value="UUID">UUID</option>
              </select>

              <!-- Nullable Check -->
              <label class="flex items-center space-x-1 cursor-pointer text-[11px] select-none">
                <input type="checkbox" v-model="col.nullable" class="rounded text-pink-600" />
                <span class="text-slate-400">Null</span>
              </label>

              <!-- Primary Key Check -->
              <label class="flex items-center space-x-1 cursor-pointer text-[11px] select-none">
                <input
                  type="checkbox"
                  v-model="col.is_primary"
                  @change="onPkToggled(col)"
                  class="rounded text-pink-600"
                />
                <span class="text-amber-500 font-bold">PK</span>
              </label>

              <!-- Auto Increment Check -->
              <label class="flex items-center space-x-1 cursor-pointer text-[11px] select-none">
                <input type="checkbox" v-model="col.auto_increment" class="rounded text-pink-600" />
                <span class="text-sky-500">AutoInc</span>
              </label>

              <!-- Default Value -->
              <input
                v-model="col.default"
                type="text"
                placeholder="Default val"
                class="w-24 scry-bg-card border scry-border rounded px-2 py-1.5 scry-text-main text-[11px] focus:outline-none"
              />

              <!-- Remove Column -->
              <button
                @click="removeColumn(index)"
                class="text-rose-500 hover:text-rose-600 p-1 font-bold cursor-pointer"
                title="Remove Column"
              >
                &times;
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right 1 Col: Live DDL Preview -->
      <div class="space-y-4">
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between border-b scry-border pb-2">
            <h4 class="font-bold text-xs uppercase tracking-wider scry-text-subtle">Generated DDL Statement</h4>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold scry-badge-glaucous">
              {{ store.currentConnection }}
            </span>
          </div>

          <pre class="p-3.5 rounded-lg bg-slate-950 text-slate-200 text-xs overflow-x-auto whitespace-pre-wrap leading-relaxed shadow-inner border border-slate-800">{{ generatedDdl }}</pre>

          <button
            @click="copyDdl"
            class="w-full py-2 rounded-lg border scry-border scry-bg-input scry-text-main hover:opacity-80 transition-opacity text-xs font-semibold cursor-pointer"
          >
            Copy DDL Statement
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useConnectionStore } from '../stores/useConnectionStore';
import { useToastStore } from '../stores/useToastStore';

const router = useRouter();
const store = useConnectionStore();
const toast = useToastStore();

const tableName = ref('new_table');
const creating = ref(false);

const columns = ref([
  { name: 'id', type: 'BIGINT', nullable: false, is_primary: true, auto_increment: true, default: '' },
  { name: 'name', type: 'VARCHAR(255)', nullable: false, is_primary: false, auto_increment: false, default: '' },
  { name: 'created_at', type: 'TIMESTAMP', nullable: true, is_primary: false, auto_increment: false, default: '' },
  { name: 'updated_at', type: 'TIMESTAMP', nullable: true, is_primary: false, auto_increment: false, default: '' },
]);

const addColumn = () => {
  columns.value.push({
    name: `column_${columns.value.length + 1}`,
    type: 'VARCHAR(255)',
    nullable: true,
    is_primary: false,
    auto_increment: false,
    default: '',
  });
};

const removeColumn = (index) => {
  columns.value.splice(index, 1);
};

const onPkToggled = (col) => {
  if (col.is_primary) {
    col.nullable = false;
  }
};

const addPresetId = () => {
  columns.value.unshift({
    name: 'id',
    type: 'BIGINT',
    nullable: false,
    is_primary: true,
    auto_increment: true,
    default: '',
  });
};

const addPresetTimestamps = () => {
  columns.value.push(
    { name: 'created_at', type: 'TIMESTAMP', nullable: true, is_primary: false, auto_increment: false, default: '' },
    { name: 'updated_at', type: 'TIMESTAMP', nullable: true, is_primary: false, auto_increment: false, default: '' }
  );
};

const addPresetUuid = () => {
  columns.value.push({
    name: 'uuid',
    type: 'UUID',
    nullable: false,
    is_primary: false,
    auto_increment: false,
    default: '',
  });
};

const generatedDdl = computed(() => {
  const t = tableName.value.trim() || 'table_name';
  const defs = columns.value.map(c => {
    const name = c.name.trim() || 'unnamed';
    const type = c.type;
    const nullable = c.nullable ? 'NULL' : 'NOT NULL';
    const pk = c.is_primary ? ' PRIMARY KEY' : '';
    const auto = c.auto_increment ? ' AUTO_INCREMENT' : '';
    const def = c.default ? ` DEFAULT '${c.default}'` : '';
    return `  "${name}" ${type} ${nullable}${auto}${pk}${def}`;
  });

  return `CREATE TABLE "${t}" (\n${defs.join(',\n')}\n);`;
});

const copyDdl = () => {
  navigator.clipboard.writeText(generatedDdl.value);
  toast.success('DDL statement copied to clipboard.');
};

const createTable = async () => {
  if (!tableName.value.trim() || columns.value.length === 0) return;

  creating.value = true;
  try {
    const res = await store.scryFetch('/schema/tables', {
      method: 'POST',
      body: JSON.stringify({
        table_name: tableName.value.trim(),
        columns: columns.value,
      }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      toast.success(`Table "${tableName.value}" created successfully!`);
      router.push(`/tables/${tableName.value.trim()}/data`);
    } else {
      toast.error('Failed to create table: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    toast.error('Request error creating table: ' + err.message);
  } finally {
    creating.value = false;
  }
};
</script>
