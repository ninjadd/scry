<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app">
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">ERD Database Schema Visualizer</h2>
        <p class="text-sm scry-text-muted">Interactive entity-relationship diagram with foreign key relationship arrows for <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center p-0.5 rounded-lg scry-bg-input border scry-border">
          <button
            @click="activeView = 'diagram'"
            class="px-3 py-1 text-xs font-semibold rounded-md transition-all cursor-pointer"
            :class="activeView === 'diagram' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-text-muted hover:scry-text-main'"
          >
            ERD Diagram View
          </button>
          <button
            @click="activeView = 'cards'"
            class="px-3 py-1 text-xs font-semibold rounded-md transition-all cursor-pointer"
            :class="activeView === 'cards' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-text-muted hover:scry-text-main'"
          >
            Cards View
          </button>
        </div>

        <button
          @click="exportMermaid"
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-pale-blue hover:opacity-80 transition-opacity cursor-pointer shadow-sm"
        >
          Export Mermaid Code
        </button>
        <button
          @click="exportSvg"
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-glaucous hover:opacity-80 transition-opacity cursor-pointer shadow-sm"
        >
          Export SVG
        </button>
        <button
          @click="exportPng"
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-sulphur hover:opacity-80 transition-opacity cursor-pointer shadow-sm"
        >
          Export PNG
        </button>
      </div>
    </div>

    <!-- Search Filter & Summary Bar -->
    <div v-if="!loading" class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 select-none">
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
          {{ totalForeignKeys }} Foreign Key Arrows
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-20 text-center scry-text-muted font-mono text-xs">
      Building entity-relationship diagram...
    </div>

    <!-- Main Visualizer Area -->
    <div v-else class="flex-1 overflow-auto scry-bg-card border scry-border rounded-xl p-6 shadow-sm flex flex-col">
      <div v-if="filteredNodes.length === 0" class="py-16 text-center text-xs scry-text-muted font-mono">
        No entities matching filter "{{ searchQuery }}".
      </div>

      <!-- Real ERD Mermaid Visualizer with Arrows -->
      <div
        v-show="activeView === 'diagram' && filteredNodes.length > 0"
        ref="diagramContainer"
        class="flex-1 overflow-auto flex items-center justify-center min-h-[500px] p-4 font-mono text-xs"
      ></div>

      <!-- Table Cards Grid View -->
      <div v-show="activeView === 'cards' && filteredNodes.length > 0" id="erd-container" ref="erdContainer" class="flex-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import mermaid from 'mermaid';
import { useConnectionStore } from '../stores/useConnectionStore';

const store = useConnectionStore();
const loading = ref(true);
const activeView = ref('diagram'); // 'diagram' or 'cards'
const schemaNodes = ref([]);
const erdContainer = ref(null);
const diagramContainer = ref(null);
const searchQuery = ref('');

mermaid.initialize({
  startOnLoad: false,
  theme: 'base',
  themeVariables: {
    fontFamily: 'Fira Code, monospace',
    fontSize: '12px',
    primaryColor: '#e1f2fa',
    primaryTextColor: '#1c262a',
    primaryBorderColor: '#b91c5c',
    lineColor: '#b91c5c',
    secondaryColor: '#e4f0ea',
    tertiaryColor: '#f8f1c8',
  },
  securityLevel: 'loose',
});

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

const generateMermaidDefinition = (nodes) => {
  let def = 'erDiagram\n';
  const sanitizeName = (name) => (name || '').replace(/[^a-zA-Z0-9_]/g, '_');
  const sanitizeType = (type) => (type || 'string').replace(/[^a-zA-Z0-9_]/g, '_');

  for (const node of nodes) {
    const tableName = sanitizeName(node.table);
    def += `    ${tableName} {\n`;
    for (const c of node.columns) {
      const colName = sanitizeName(c.name);
      const colType = sanitizeType(c.data_type || c.full_type);
      const pkFk = c.is_primary ? 'PK' : (c.is_foreign_key ? 'FK' : '');
      def += `        ${colType} ${colName} ${pkFk}\n`;
    }
    def += `    }\n`;

    if (node.foreign_keys) {
      for (const fk of node.foreign_keys) {
        const targetTable = sanitizeName(fk.foreign_table_name);
        const sourceCol = sanitizeName(fk.column_name);
        def += `    ${tableName} }|..|| ${targetTable} : "${sourceCol}"\n`;
      }
    }
  }
  return def;
};

const renderDiagram = async () => {
  if (filteredNodes.value.length === 0) return;
  await nextTick();
  const def = generateMermaidDefinition(filteredNodes.value);
  try {
    const id = `mermaid-erd-${Date.now()}`;
    const { svg } = await mermaid.render(id, def);
    if (diagramContainer.value) {
      diagramContainer.value.innerHTML = svg;
    }
  } catch (err) {
    console.error('Failed to render ERD diagram:', err);
  }
};

const loadFullSchema = async () => {
  loading.value = true;
  schemaNodes.value = [];

  try {
    const res = await store.scryFetch('/schema/full');
    if (res.ok) {
      const data = await res.json();
      schemaNodes.value = data.schemas || [];
    }
  } catch (err) {
    console.error('Failed to load full schema:', err);
  } finally {
    loading.value = false;
    renderDiagram();
  }
};

const exportMermaid = () => {
  const def = generateMermaidDefinition(filteredNodes.value);
  const blob = new Blob([def], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `schema_${store.currentConnection}.mmd`;
  a.click();
};

const exportSvg = () => {
  const svgEl = diagramContainer.value ? diagramContainer.value.querySelector('svg') : null;
  if (!svgEl) {
    alert('No ERD diagram SVG available to export.');
    return;
  }
  const svgData = new XMLSerializer().serializeToString(svgEl);
  const blob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `erd_${store.currentConnection}.svg`;
  a.click();
};

const exportPng = () => {
  const svgEl = diagramContainer.value ? diagramContainer.value.querySelector('svg') : null;
  if (!svgEl) {
    alert('No ERD diagram SVG available to export.');
    return;
  }

  const svgData = new XMLSerializer().serializeToString(svgEl);
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d');
  const img = new Image();

  const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
  const url = URL.createObjectURL(svgBlob);

  img.onload = () => {
    const bbox = svgEl.getBoundingClientRect();
    const width = Math.max((bbox.width || 1200), 800) * 2;
    const height = Math.max((bbox.height || 800), 600) * 2;

    canvas.width = width;
    canvas.height = height;

    ctx.fillStyle = '#faf9f5';
    ctx.fillRect(0, 0, width, height);
    ctx.drawImage(img, 0, 0, width, height);

    URL.revokeObjectURL(url);

    const pngUrl = canvas.toDataURL('image/png');
    const a = document.createElement('a');
    a.href = pngUrl;
    a.download = `erd_${store.currentConnection}.png`;
    a.click();
  };

  img.src = url;
};

onMounted(loadFullSchema);
watch(searchQuery, renderDiagram);
watch(activeView, (newVal) => {
  if (newVal === 'diagram') renderDiagram();
});
watch(() => store.currentConnection, loadFullSchema);
</script>

