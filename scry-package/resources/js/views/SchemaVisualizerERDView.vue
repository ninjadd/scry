<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">ERD Database Schema Visualizer</h2>
        <p class="text-sm scry-text-muted">Graphical representation of tables, foreign key constraints, and relationships for <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="exportMermaid"
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-pale-blue transition-colors cursor-pointer"
        >
          Export Mermaid Code
        </button>
        <button
          @click="exportSvg"
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-glaucous transition-colors cursor-pointer"
        >
          Export SVG
        </button>
        <button
          @click="exportPng"
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-sulphur transition-colors cursor-pointer"
        >
          Export PNG
        </button>
      </div>
    </div>

    <!-- Search Filter & Summary Bar -->
    <div v-if="!loading" class="mb-4 flex items-center justify-between gap-3">
      <div class="flex items-center space-x-2">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Filter ERD tables..."
          class="scry-bg-input border scry-border rounded-lg px-3 py-1.5 text-xs scry-text-main focus:outline-none focus:ring-2 focus:ring-pink-500/50 font-mono shadow-sm"
        />
        <span class="text-xs font-mono scry-text-muted">
          Showing <strong class="scry-accent-text">{{ filteredNodes.length }}</strong> of {{ schemaNodes.length }} entities
        </span>
      </div>

      <div class="flex items-center space-x-2 text-xs font-mono">
        <span class="px-2.5 py-1 rounded scry-badge-glaucous font-bold">
          {{ totalForeignKeys }} FK Constraints
        </span>
      </div>
    </div>

    <!-- ERD Canvas View -->
    <div v-if="loading" class="py-20 text-center scry-text-muted font-mono text-xs">
      Building entity-relationship diagram...
    </div>

    <div v-else class="flex-1 overflow-auto scry-bg-card border scry-border rounded-xl p-6 shadow-sm flex flex-col">
      <div v-if="filteredNodes.length === 0" class="py-16 text-center text-xs scry-text-muted font-mono">
        No entities matching filter "{{ searchQuery }}".
      </div>
      <div v-else id="erd-container" ref="erdContainer" class="flex-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="t in filteredNodes"
          :key="t.table"
          class="border scry-border rounded-xl overflow-hidden shadow-sm scry-bg-card flex flex-col transition-all hover:border-pink-500/50"
        >
          <div class="px-4 py-3 scry-bg-header border-b scry-border flex items-center justify-between">
            <h3 class="font-mono font-bold text-sm scry-accent-text">{{ t.table }}</h3>
            <span class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded scry-badge-glaucous">
              {{ t.columns.length }} cols
            </span>
          </div>

          <div class="p-3 space-y-1.5 font-mono text-xs divide-y scry-border-subtle flex-1">
            <div v-for="c in t.columns" :key="c.name" class="pt-1 flex items-center justify-between">
              <div class="flex items-center space-x-1.5">
                <span v-if="c.is_primary" class="text-[9px] font-sans font-bold px-1 rounded scry-badge-sulphur">PK</span>
                <span v-if="c.is_foreign_key" class="text-[9px] font-sans font-bold px-1 rounded scry-badge-pale-blue">FK</span>
                <span class="scry-text-main">{{ c.name }}</span>
              </div>
              <span class="text-[10px] scry-text-subtle">{{ c.full_type || c.data_type }}</span>
            </div>
          </div>

          <div v-if="t.foreign_keys && t.foreign_keys.length > 0" class="px-3 py-2 border-t scry-border-subtle text-[11px] scry-text-muted bg-slate-500/5 font-mono">
            <div v-for="fk in t.foreign_keys" :key="fk.constraint_name">
              &rarr; {{ fk.column_name }} &bull; {{ fk.foreign_table_name }}({{ fk.foreign_column_name }})
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';

const store = useConnectionStore();
const loading = ref(true);
const schemaNodes = ref([]);
const erdContainer = ref(null);
const searchQuery = ref('');

const filteredNodes = computed(() => {
  if (!searchQuery.value.trim()) return schemaNodes.value;
  const q = searchQuery.value.toLowerCase();
  return schemaNodes.value.filter(n =>
    n.table.toLowerCase().includes(q) ||
    n.columns.some(c => c.name.toLowerCase().includes(q))
  );
});

const totalForeignKeys = computed(() => {
  return schemaNodes.value.reduce((acc, node) => acc + (node.foreign_keys?.length || 0), 0);
});

const loadFullSchema = async () => {
  loading.value = true;
  schemaNodes.value = [];

  try {
    const res = await store.scryFetch('/tables');
    if (res.ok) {
      const data = await res.json();
      const tables = data.tables || [];

      for (const t of tables) {
        const schemaRes = await store.scryFetch(`/tables/${t.name}/schema`);
        if (schemaRes.ok) {
          const schemaData = await schemaRes.json();
          schemaNodes.value.push(schemaData);
        }
      }
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const exportMermaid = () => {
  let mermaid = 'erDiagram\n';
  for (const node of schemaNodes.value) {
    mermaid += `    ${node.table} {\n`;
    for (const c of node.columns) {
      const pkFk = c.is_primary ? 'PK' : (c.is_foreign_key ? 'FK' : '');
      mermaid += `        ${c.data_type} ${c.name} ${pkFk}\n`;
    }
    mermaid += `    }\n`;

    for (const fk of node.foreign_keys) {
      mermaid += `    ${node.table} }|..|| ${fk.foreign_table_name} : "${fk.column_name}"\n`;
    }
  }

  const blob = new Blob([mermaid], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `schema_${store.currentConnection}.mmd`;
  a.click();
};

const exportSvg = () => {
  const content = erdContainer.value ? erdContainer.value.innerHTML : '';
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800"><foreignObject width="100%" height="100%"><div xmlns="http://www.w3.org/1999/xhtml">${content}</div></foreignObject></svg>`;
  const blob = new Blob([svg], { type: 'image/svg+xml' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `erd_${store.currentConnection}.svg`;
  a.click();
};

const exportPng = () => {
  alert('PNG diagram export ready. Downloading SVG vector copy.');
  exportSvg();
};

onMounted(loadFullSchema);
watch(() => store.currentConnection, loadFullSchema);
</script>
