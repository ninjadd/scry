<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app select-none">
    <!-- Header -->
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Interactive ERD Schema Visualizer</h2>
        <p class="text-sm scry-text-muted">
          Entity-Relationship Diagram visualizer powered by Mermaid.js with dynamic canvas controls and export tools for 
          <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center p-0.5 rounded-lg scry-bg-input border scry-border">
          <button
            @click="activeView = 'diagram'"
            class="px-3 py-1 text-xs font-semibold rounded-md transition-all cursor-pointer"
            :class="activeView === 'diagram' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-text-muted hover:scry-text-main'"
          >
            ERD Visualizer
          </button>
          <button
            @click="activeView = 'cards'"
            class="px-3 py-1 text-xs font-semibold rounded-md transition-all cursor-pointer"
            :class="activeView === 'cards' ? 'scry-accent-bg font-bold shadow-sm' : 'scry-text-muted hover:scry-text-main'"
          >
            Schema Cards
          </button>
        </div>

        <button
          @click="exportMermaidCode"
          class="px-3 py-1.5 text-xs font-semibold rounded-md scry-badge-pale-blue hover:opacity-80 transition-opacity cursor-pointer shadow-sm"
        >
          Copy Mermaid
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
        <button
          @click="loadSchema"
          class="px-3 py-1.5 text-xs font-semibold rounded-md border scry-border scry-bg-card scry-text-main hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer shadow-sm"
        >
          Refresh
        </button>
      </div>
    </div>

    <!-- Filter & Statistics Bar -->
    <div v-if="!loading" class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
      <div class="flex items-center space-x-2">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Filter tables in diagram..."
          class="scry-bg-input border scry-border rounded-lg px-3 py-1.5 text-xs scry-text-main focus:outline-none focus:ring-2 focus:ring-pink-500 font-mono shadow-sm"
        />
        <span class="text-xs font-mono scry-text-muted">
          Showing <strong class="scry-accent-text">{{ filteredTables.length }}</strong> of {{ tables.length }} tables
        </span>
      </div>

      <div class="flex items-center space-x-2 text-xs font-mono">
        <span class="px-2.5 py-1 rounded scry-badge-glaucous font-bold">
          {{ relationships.length }} Foreign Key Relationships
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-20 text-center scry-text-muted font-mono text-xs">
      Building entity-relationship diagram for {{ store.currentConnection }}...
    </div>

    <!-- Main Canvas View -->
    <div v-show="!loading" class="flex-1 overflow-hidden scry-bg-card border scry-border rounded-xl shadow-sm flex flex-col relative">
      <div v-if="filteredTables.length === 0" class="py-16 text-center text-xs scry-text-muted font-mono">
        No tables matching "{{ searchQuery }}".
      </div>

      <!-- Interactive Mermaid Canvas Wrapper -->
      <div
        v-show="activeView === 'diagram' && filteredTables.length > 0"
        ref="canvasWrapper"
        class="relative flex-1 w-full h-full overflow-hidden cursor-grab active:cursor-grabbing"
        @mousedown="startPan"
        @mousemove="onPan"
        @mouseup="endPan"
        @mouseleave="endPan"
        @wheel.prevent="onWheel"
      >
        <!-- Floating Canvas Controls -->
        <div class="absolute top-4 right-4 z-30 flex items-center space-x-1 p-1 rounded-lg scry-bg-card border scry-border shadow-lg text-xs font-mono select-none">
          <button
            @click="zoomIn"
            class="px-2.5 py-1 rounded hover:scry-bg-input scry-text-main font-bold cursor-pointer transition-colors"
            title="Zoom In (+)"
          >+</button>

          <span class="px-2 py-1 text-[11px] font-bold scry-accent-text min-w-[50px] text-center">
            {{ Math.round(zoomScale * 100) }}%
          </span>

          <button
            @click="zoomOut"
            class="px-2.5 py-1 rounded hover:scry-bg-input scry-text-main font-bold cursor-pointer transition-colors"
            title="Zoom Out (-)"
          >&minus;</button>

          <button
            @click="resetView"
            class="px-2.5 py-1 rounded hover:scry-bg-input scry-text-main font-semibold cursor-pointer transition-colors text-[11px]"
            title="Reset Canvas View"
          >Reset</button>
        </div>

        <!-- Rendered Mermaid SVG Container -->
        <div
          class="mermaid-container w-full h-full flex items-center justify-center p-8 transition-transform duration-75 origin-center"
          :style="{
            transform: `translate(${panOffset.x}px, ${panOffset.y}px) scale(${zoomScale})`,
          }"
        >
          <div ref="mermaidOutput" class="mermaid-target"></div>
        </div>
      </div>

      <!-- Schema Cards View (Alternate Tab) -->
      <div
        v-show="activeView === 'cards'"
        class="flex-1 overflow-y-auto p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
      >
        <div
          v-for="t in filteredTables"
          :key="t.name"
          class="p-4 rounded-xl border scry-border scry-bg-input shadow-sm space-y-3 flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between border-b scry-border-subtle pb-2 mb-2">
              <div class="flex items-center space-x-1.5">
                <span class="font-mono font-bold text-sm scry-accent-text">{{ t.name }}</span>
              </div>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded scry-badge-glaucous font-bold">
                {{ t.rows }} rows
              </span>
            </div>

            <!-- Columns List -->
            <div class="space-y-1 max-h-48 overflow-y-auto font-mono text-xs">
              <div
                v-for="c in t.columns"
                :key="c.name"
                class="flex items-center justify-between py-0.5 border-b border-slate-500/10 text-[11px]"
              >
                <div class="flex items-center space-x-1">
                  <span v-if="c.is_primary" class="text-amber-500 font-bold">🔑</span>
                  <span v-else-if="c.is_foreign_key" class="text-sky-500 font-bold">🔗</span>
                  <span :class="c.is_primary ? 'font-bold scry-text-main' : 'scry-text-muted'">{{ c.name }}</span>
                </div>
                <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ c.data_type || c.type }}</span>
              </div>
            </div>
          </div>

          <!-- Foreign Key Relations -->
          <div v-if="t.foreign_keys && t.foreign_keys.length > 0" class="pt-2 border-t scry-border text-[11px] font-mono">
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Foreign Keys</span>
            <div v-for="fk in t.foreign_keys" :key="fk.constraint_name" class="text-sky-600 dark:text-sky-400 truncate">
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
import { useConnectionStore } from '../stores/useConnectionStore';
import { useToastStore } from '../stores/useToastStore';
import mermaid from 'mermaid';

const store = useConnectionStore();
const toast = useToastStore();

const loading = ref(true);
const activeView = ref('diagram'); // 'diagram' | 'cards'
const searchQuery = ref('');

const tables = ref([]);
const relationships = ref([]);

const mermaidOutput = ref(null);
const canvasWrapper = ref(null);

const zoomScale = ref(1.0);
const panOffset = ref({ x: 0, y: 0 });
const isPanning = ref(false);
const panStart = ref({ x: 0, y: 0 });

const filteredTables = computed(() => {
  if (!searchQuery.value.trim()) return tables.value;
  const q = searchQuery.value.toLowerCase();
  return tables.value.filter(t => t.name.toLowerCase().includes(q));
});

const loadSchema = async () => {
  loading.value = true;
  try {
    const res = await store.scryFetch('/schema/relationships');
    if (res.ok) {
      const data = await res.json();
      tables.value = data.tables || [];
      relationships.value = data.relationships || [];
    }
  } catch (err) {
    console.error(err);
    toast.error('Failed to load schema relationships.');
  } finally {
    loading.value = false;
    await nextTick();
    await renderMermaidDiagram();
  }
};

const generateMermaidSyntax = () => {
  const visibleTableNames = new Set(filteredTables.value.map(t => t.name));
  let syntax = 'erDiagram\n';

  // Tables & Column definitions
  for (const t of filteredTables.value) {
    const cleanName = t.name.replace(/[^a-zA-Z0-9_]/g, '_');
    syntax += `    ${cleanName} {\n`;
    for (const c of t.columns.slice(0, 15)) {
      const cleanCol = c.name.replace(/[^a-zA-Z0-9_]/g, '_');
      const rawType = c.data_type || c.type || 'string';
      const cleanType = rawType.replace(/[^a-zA-Z0-9_]/g, '_') || 'string';
      const pkBadge = c.is_primary ? ' PK' : (c.is_foreign_key ? ' FK' : '');
      syntax += `        ${cleanType} ${cleanCol}${pkBadge}\n`;
    }
    syntax += `    }\n`;
  }

  // Relationship lines
  for (const r of relationships.value) {
    if (visibleTableNames.has(r.from_table) && visibleTableNames.has(r.to_table)) {
      const fromClean = r.from_table.replace(/[^a-zA-Z0-9_]/g, '_');
      const toClean = r.to_table.replace(/[^a-zA-Z0-9_]/g, '_');
      const label = (r.from_column || 'fk').replace(/[^a-zA-Z0-9_]/g, '_');
      // Many-to-one relationship
      syntax += `    ${toClean} ||--o{ ${fromClean} : "${label}"\n`;
    }
  }

  return syntax;
};

const renderMermaidDiagram = async () => {
  await nextTick();
  if (!mermaidOutput.value || filteredTables.value.length === 0) return;

  const isDark = document.documentElement.classList.contains('dark');
  mermaid.initialize({
    startOnLoad: false,
    theme: isDark ? 'dark' : 'default',
    er: {
      useMaxWidth: false,
      layoutDirection: 'TB',
    },
    securityLevel: 'loose',
  });

  const syntax = generateMermaidSyntax();
  try {
    const id = `mermaid-erd-${Date.now()}`;
    const { svg } = await mermaid.render(id, syntax);
    mermaidOutput.value.innerHTML = svg;
  } catch (err) {
    console.error('Mermaid render error:', err);
    mermaidOutput.value.innerHTML = `<div class="p-4 text-xs font-mono text-rose-500">Failed to render Mermaid ERD graph: ${err.message}</div>`;
  }
};

const zoomIn = () => {
  zoomScale.value = Math.min(zoomScale.value + 0.15, 3.0);
};

const zoomOut = () => {
  zoomScale.value = Math.max(zoomScale.value - 0.15, 0.3);
};

const resetView = () => {
  zoomScale.value = 1.0;
  panOffset.value = { x: 0, y: 0 };
};

const onWheel = (e) => {
  if (e.ctrlKey || e.metaKey) {
    const delta = e.deltaY > 0 ? -0.1 : 0.1;
    zoomScale.value = Math.max(0.3, Math.min(3.0, zoomScale.value + delta));
  } else {
    panOffset.value.x -= e.deltaX;
    panOffset.value.y -= e.deltaY;
  }
};

const startPan = (e) => {
  if (e.target.closest('button')) return;
  isPanning.value = true;
  panStart.value = { x: e.clientX - panOffset.value.x, y: e.clientY - panOffset.value.y };
};

const onPan = (e) => {
  if (!isPanning.value) return;
  panOffset.value = {
    x: e.clientX - panStart.value.x,
    y: e.clientY - panStart.value.y,
  };
};

const endPan = () => {
  isPanning.value = false;
};

const exportMermaidCode = () => {
  const code = generateMermaidSyntax();
  navigator.clipboard.writeText(code);
  toast.success('Mermaid syntax copied to clipboard!');
};

const exportSvg = () => {
  const svgEl = mermaidOutput.value?.querySelector('svg');
  if (!svgEl) {
    toast.error('No SVG element found to export.');
    return;
  }

  const svgData = new XMLSerializer().serializeToString(svgEl);
  const blob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `erd_schema_${store.currentConnection}_${Date.now()}.svg`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  toast.success('ERD SVG exported successfully.');
};

const exportPng = () => {
  const svgEl = mermaidOutput.value?.querySelector('svg');
  if (!svgEl) {
    toast.error('No SVG element found to export.');
    return;
  }

  const svgData = new XMLSerializer().serializeToString(svgEl);
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d');
  const img = new Image();

  const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
  const url = URL.createObjectURL(svgBlob);

  img.onload = () => {
    canvas.width = img.width * 2;
    canvas.height = img.height * 2;
    ctx.fillStyle = '#1e293b';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

    const pngUrl = canvas.toDataURL('image/png');
    const link = document.createElement('a');
    link.href = pngUrl;
    link.download = `erd_schema_${store.currentConnection}_${Date.now()}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    toast.success('ERD PNG exported successfully.');
  };

  img.src = url;
};

onMounted(loadSchema);
watch(() => store.currentConnection, loadSchema);
watch(activeView, async (newVal) => {
  if (newVal === 'diagram') {
    await renderMermaidDiagram();
  }
});
watch(searchQuery, async () => {
  await renderMermaidDiagram();
});
</script>

<style scoped>
.mermaid-target :deep(svg) {
  max-width: none !important;
  height: auto !important;
}
</style>
