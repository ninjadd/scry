<template>
  <div class="flex-1 flex flex-col overflow-hidden p-6 scry-bg-app select-none">
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
    <div v-else class="flex-1 overflow-hidden scry-bg-card border scry-border rounded-xl shadow-sm flex flex-col relative">
      <div v-if="filteredNodes.length === 0" class="py-16 text-center text-xs scry-text-muted font-mono">
        No entities matching filter "{{ searchQuery }}".
      </div>

      <!-- Real ERD Mermaid Visualizer with Zoom & Pan Canvas -->
      <div
        v-show="activeView === 'diagram' && filteredNodes.length > 0"
        class="relative flex-1 w-full h-full overflow-hidden"
      >
        <!-- Floating Canvas Zoom Controls -->
        <div class="absolute top-4 right-4 z-30 flex items-center space-x-1 p-1 rounded-lg scry-bg-card border scry-border shadow-lg text-xs font-mono select-none">
          <button
            @click="zoomIn"
            class="px-2 py-1 rounded hover:scry-bg-input scry-text-main font-bold cursor-pointer transition-colors"
            title="Zoom In (+)"
          >+</button>

          <span class="px-2 py-1 text-[11px] font-bold scry-accent-text min-w-[45px] text-center">
            {{ Math.round(zoomScale * 100) }}%
          </span>

          <button
            @click="zoomOut"
            class="px-2 py-1 rounded hover:scry-bg-input scry-text-main font-bold cursor-pointer transition-colors"
            title="Zoom Out (-)"
          >&minus;</button>

          <div class="h-4 w-px bg-slate-300 dark:bg-slate-700 my-auto mx-1"></div>

          <button
            @click="resetZoom"
            class="px-2.5 py-1 rounded hover:scry-bg-input scry-text-main cursor-pointer transition-colors text-[11px] font-semibold"
            title="Reset Zoom & Pan"
          >Reset</button>
        </div>

        <!-- Canvas Viewport -->
        <div
          ref="viewportContainer"
          @wheel.prevent="handleWheelZoom"
          @mousedown="handleCanvasPanStart"
          class="w-full h-full overflow-hidden relative cursor-grab active:cursor-grabbing flex items-center justify-center"
        >
          <div
            ref="diagramContainer"
            :style="{
              transform: `translate(${panX}px, ${panY}px) scale(${zoomScale})`,
              transformOrigin: 'center center',
              transition: isPanning || isDraggingNode ? 'none' : 'transform 0.1s ease-out'
            }"
            class="inline-block p-12 font-mono text-xs"
          ></div>
        </div>
      </div>

      <!-- Table Cards Grid View -->
      <div v-show="activeView === 'cards' && filteredNodes.length > 0" id="erd-container" ref="erdContainer" class="flex-1 overflow-auto p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
const renderError = ref('');
const activeView = ref('diagram'); // 'diagram' or 'cards'
const schemaNodes = ref([]);
const erdContainer = ref(null);
const diagramContainer = ref(null);
const viewportContainer = ref(null);
const searchQuery = ref('');

// Zoom & Pan Canvas Reactive State
const zoomScale = ref(1.0);
const panX = ref(0);
const panY = ref(0);
const isPanning = ref(false);
const isDraggingNode = ref(false);

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

// Canvas Zoom & Pan Actions
const zoomIn = () => {
  zoomScale.value = Math.min(zoomScale.value + 0.15, 2.5);
};

const zoomOut = () => {
  zoomScale.value = Math.max(zoomScale.value - 0.15, 0.4);
};

const resetZoom = () => {
  zoomScale.value = 1.0;
  panX.value = 0;
  panY.value = 0;
};

const handleWheelZoom = (e) => {
  if (e.deltaY < 0) {
    zoomIn();
  } else {
    zoomOut();
  }
};

const handleCanvasPanStart = (e) => {
  if (e.target.closest('g.node, g[id*="entity"], g.entityBox')) return;
  isPanning.value = true;
  let startX = e.clientX - panX.value;
  let startY = e.clientY - panY.value;

  const onMouseMove = (moveEvent) => {
    if (!isPanning.value) return;
    panX.value = moveEvent.clientX - startX;
    panY.value = moveEvent.clientY - startY;
  };

  const onMouseUp = () => {
    isPanning.value = false;
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', onMouseUp);
  };

  window.addEventListener('mousemove', onMouseMove);
  window.addEventListener('mouseup', onMouseUp);
};

const generateMermaidDefinition = (nodes) => {
  let def = 'erDiagram\n';
  const sanitizeName = (name) => (name || '').replace(/[^a-zA-Z0-9_]/g, '_');
  const sanitizeType = (type) => {
    let clean = (type || 'string').toLowerCase().replace(/\(.*?\)/g, '').replace(/\[\]/g, '_array').replace(/[^a-zA-Z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_+|_+$/g, '');
    return clean || 'string';
  };

  const validTableNames = new Set(nodes.map(n => sanitizeName(n.table)));

  for (const node of nodes) {
    const tableName = sanitizeName(node.table);
    def += `    ${tableName} {\n`;
    for (const c of node.columns) {
      const colName = sanitizeName(c.name);
      const colType = sanitizeType(c.data_type || c.full_type);
      const pkFk = c.is_primary ? (c.is_foreign_key ? 'PK "FK"' : 'PK') : (c.is_foreign_key ? 'FK' : '');
      def += `        ${colType} ${colName} ${pkFk}\n`;
    }
    def += `    }\n`;

    if (node.foreign_keys) {
      for (const fk of node.foreign_keys) {
        const targetTable = sanitizeName(fk.foreign_table_name);
        const sourceCol = sanitizeName(fk.column_name);
        if (targetTable && sourceCol && validTableNames.has(targetTable)) {
          def += `    ${tableName} }|..|| ${targetTable} : "${sourceCol}"\n`;
        }
      }
    }
  }
  return def;
};

const makeSvgNodesDraggable = (svgEl) => {
  if (!svgEl) return;

  const nodeOffsets = new Map();
  const edgeMap = [];

  const nodeGroups = svgEl.querySelectorAll('g.node, g[id*="entity"], g.entityBox');
  const nodeMap = new Map();

  nodeGroups.forEach((node) => {
    let idAttr = node.getAttribute('id') || '';
    let textContent = node.textContent || '';
    let tableName = '';
    const idMatch = /entity-([a-zA-Z0-9_]+)/.exec(idAttr);
    if (idMatch) {
      tableName = idMatch[1];
    } else {
      const match = textContent.match(/([a-zA-Z0-9_]+)/);
      if (match) tableName = match[1];
    }
    if (tableName) {
      nodeMap.set(node, tableName);
      nodeOffsets.set(tableName, { dx: 0, dy: 0 });
    }
  });

  const paths = svgEl.querySelectorAll('g.edgePaths path, path[id*="L-"], path.edge-thickness-normal');
  paths.forEach((path) => {
    let idStr = (path.getAttribute('id') || '') + ' ' + (path.getAttribute('class') || '');
    let match = /L-([a-zA-Z0-9_]+)-([a-zA-Z0-9_]+)/.exec(idStr);
    if (!match && path.parentElement) {
      idStr += ' ' + (path.parentElement.getAttribute('id') || '') + ' ' + (path.parentElement.getAttribute('class') || '');
      match = /L-([a-zA-Z0-9_]+)-([a-zA-Z0-9_]+)/.exec(idStr);
    }
    if (match) {
      const sourceTable = match[1];
      const targetTable = match[2];
      const origD = path.getAttribute('d') || '';

      const labelEl = svgEl.querySelector(`g.edgeLabels [id*="${sourceTable}"][id*="${targetTable}"], g.edgeLabel[id*="${sourceTable}"]`);
      let origLabelTransform = labelEl ? (labelEl.getAttribute('transform') || '') : '';

      edgeMap.push({
        pathEl: path,
        labelEl,
        sourceTable,
        targetTable,
        origD,
        origLabelTransform
      });
    }
  });

  const updatePathD = (dStr, srcDx, srcDy, tgtDx, tgtDy) => {
    if (!dStr) return dStr;
    const parts = dStr.trim().split(/\s*([MCcLLHVvCSQTTAZz])\s*/).filter(Boolean);
    let newD = '';
    let i = 0;
    while (i < parts.length) {
      const cmd = parts[i];
      if (cmd === 'M' || cmd === 'L') {
        const coords = (parts[i + 1] || '').trim().split(/[\s,]+/).map(Number);
        if (coords.length >= 2) {
          const isStart = (i === 0);
          const dx = isStart ? srcDx : tgtDx;
          const dy = isStart ? srcDy : tgtDy;
          newD += `${cmd} ${coords[0] + dx} ${coords[1] + dy} `;
        }
        i += 2;
      } else if (cmd === 'C') {
        const coords = (parts[i + 1] || '').trim().split(/[\s,]+/).map(Number);
        if (coords.length >= 6) {
          newD += `C ${coords[0] + srcDx} ${coords[1] + srcDy}, ${coords[2] + tgtDx} ${coords[3] + tgtDy}, ${coords[4] + tgtDx} ${coords[5] + tgtDy} `;
        }
        i += 2;
      } else {
        newD += `${cmd} ${parts[i + 1] || ''} `;
        i += 2;
      }
    }
    return newD.trim();
  };

  const updateConnectedEdges = (draggedTable) => {
    edgeMap.forEach((edge) => {
      if (edge.sourceTable === draggedTable || edge.targetTable === draggedTable) {
        const srcOffset = nodeOffsets.get(edge.sourceTable) || { dx: 0, dy: 0 };
        const tgtOffset = nodeOffsets.get(edge.targetTable) || { dx: 0, dy: 0 };

        const newD = updatePathD(edge.origD, srcOffset.dx, srcOffset.dy, tgtOffset.dx, tgtOffset.dy);
        edge.pathEl.setAttribute('d', newD);

        if (edge.labelEl && edge.origLabelTransform) {
          const midDx = (srcOffset.dx + tgtOffset.dx) / 2;
          const midDy = (srcOffset.dy + tgtOffset.dy) / 2;

          let labelMatch = /translate\(\s*([-\d.]+)[,\s]+([-\d.]+)\s*\)/.exec(edge.origLabelTransform);
          if (labelMatch) {
            const lx = parseFloat(labelMatch[1]) + midDx;
            const ly = parseFloat(labelMatch[2]) + midDy;
            edge.labelEl.setAttribute('transform', edge.origLabelTransform.replace(/translate\(\s*([-\d.]+)[,\s]+([-\d.]+)\s*\)/, `translate(${lx}, ${ly})`));
          }
        }
      }
    });
  };

  nodeGroups.forEach((node) => {
    const tableName = nodeMap.get(node);
    node.style.cursor = 'grab';

    node.addEventListener('mousedown', (e) => {
      e.stopPropagation();
      isDraggingNode.value = true;
      let isDragging = true;
      node.style.cursor = 'grabbing';

      let currentTransform = node.getAttribute('transform') || '';
      let match = /translate\(\s*([-\d.]+)[,\s]+([-\d.]+)\s*\)/.exec(currentTransform);
      let initialX = match ? parseFloat(match[1]) : 0;
      let initialY = match ? parseFloat(match[2]) : 0;

      let startX = e.clientX;
      let startY = e.clientY;

      const onMouseMove = (moveEvent) => {
        if (!isDragging) return;
        moveEvent.preventDefault();
        const dx = (moveEvent.clientX - startX) / zoomScale.value;
        const dy = (moveEvent.clientY - startY) / zoomScale.value;
        const newX = initialX + dx;
        const newY = initialY + dy;

        if (match) {
          node.setAttribute('transform', currentTransform.replace(/translate\(\s*([-\d.]+)[,\s]+([-\d.]+)\s*\)/, `translate(${newX}, ${newY})`));
        } else {
          node.setAttribute('transform', `translate(${newX}, ${newY}) ${currentTransform}`);
        }

        if (tableName) {
          nodeOffsets.set(tableName, { dx: newX - initialX, dy: newY - initialY });
          updateConnectedEdges(tableName);
        }
      };

      const onMouseUp = () => {
        isDragging = false;
        isDraggingNode.value = false;
        node.style.cursor = 'grab';
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
      };

      window.addEventListener('mousemove', onMouseMove);
      window.addEventListener('mouseup', onMouseUp);
    });
  });
};

const renderDiagram = async () => {
  renderError.value = '';
  if (filteredNodes.value.length === 0) return;
  await nextTick();
  const def = generateMermaidDefinition(filteredNodes.value);
  try {
    const id = `mermaid-erd-${Date.now()}`;
    const { svg } = await mermaid.render(id, def);
    if (diagramContainer.value) {
      diagramContainer.value.innerHTML = svg;
      const svgEl = diagramContainer.value.querySelector('svg');
      if (svgEl) {
        svgEl.style.overflow = 'visible';
        svgEl.style.padding = '60px 40px 40px 40px';
        svgEl.style.maxWidth = 'none';
        makeSvgNodesDraggable(svgEl);
      }
    }
  } catch (err) {
    console.error('Failed to render ERD diagram:', err);
    renderError.value = err.message || 'Failed to render ERD diagram';
    if (diagramContainer.value) {
      diagramContainer.value.innerHTML = `<div class="p-4 text-center text-rose-500 font-mono text-xs">Error rendering ERD diagram: ${err.message || 'Syntax or rendering error'}</div>`;
    }
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

  try {
    const clone = svgEl.cloneNode(true);
    clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    clone.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');

    const bbox = svgEl.getBoundingClientRect();
    const width = Math.max(bbox.width || 1200, 1000);
    const height = Math.max(bbox.height || 900, 800);

    clone.setAttribute('width', width.toString());
    clone.setAttribute('height', height.toString());
    clone.style.backgroundColor = '#faf9f5';

    const svgString = new XMLSerializer().serializeToString(clone);
    const svgDataUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgString);

    const img = new Image();
    img.crossOrigin = 'anonymous';

    img.onload = () => {
      const canvas = document.createElement('canvas');
      canvas.width = width * 2;
      canvas.height = height * 2;
      const ctx = canvas.getContext('2d');
      ctx.scale(2, 2);
      ctx.fillStyle = '#faf9f5';
      ctx.fillRect(0, 0, width, height);
      ctx.drawImage(img, 0, 0, width, height);

      const pngUrl = canvas.toDataURL('image/png');
      const a = document.createElement('a');
      a.href = pngUrl;
      a.download = `erd_${store.currentConnection}.png`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    };

    img.onerror = (e) => {
      console.error('PNG conversion error:', e);
      alert('Could not convert SVG to PNG image.');
    };

    img.src = svgDataUrl;
  } catch (err) {
    console.error('Export PNG failed:', err);
    alert('Failed to export PNG: ' + err.message);
  }
};

onMounted(loadFullSchema);
watch(searchQuery, renderDiagram);
watch(activeView, (newVal) => {
  if (newVal === 'diagram') renderDiagram();
});
watch(() => store.currentConnection, loadFullSchema);
</script>
