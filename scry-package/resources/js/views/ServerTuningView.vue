<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-3 select-none">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Server Performance & Slow Query Advisor</h2>
        <p class="text-sm scry-text-muted">Real-time process monitor, slow query diagnostics, and optimization recommendations for <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="loadAll"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm cursor-pointer"
        >
          Re-analyze & Refresh
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-20 text-center scry-text-muted font-mono text-xs">
      Analyzing server variables & active processes...
    </div>

    <div v-else class="space-y-6">
      <!-- Active Processes & Slow Query Monitor -->
      <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <span class="text-xs font-mono font-bold uppercase px-2 py-0.5 rounded scry-badge-glaucous">
              PROCESSLIST
            </span>
            <h3 class="font-bold text-base scry-text-main">Active Queries & Process Monitor</h3>
          </div>

          <span class="text-xs font-mono scry-text-muted">
            <strong class="scry-accent-text">{{ processes.length }}</strong> active session(s)
          </span>
        </div>

        <div v-if="processes.length === 0" class="py-8 text-center text-xs scry-text-muted font-mono bg-slate-500/5 rounded-lg border scry-border">
          No active non-idle queries currently executing.
        </div>

        <div v-else class="overflow-x-auto border scry-border rounded-lg">
          <table class="w-full text-left text-xs font-mono select-none">
            <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
              <tr>
                <th class="px-3 py-2.5">PID</th>
                <th class="px-3 py-2.5">User</th>
                <th class="px-3 py-2.5">Host</th>
                <th class="px-3 py-2.5">State</th>
                <th class="px-3 py-2.5">Duration</th>
                <th class="px-3 py-2.5">SQL Query Info</th>
                <th class="px-3 py-2.5 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y scry-border-subtle scry-text-main">
              <tr v-for="p in processes" :key="p.pid" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <td class="px-3 py-2.5 font-bold scry-accent-text">{{ p.pid }}</td>
                <td class="px-3 py-2.5">{{ p.user }}</td>
                <td class="px-3 py-2.5 scry-text-subtle">{{ p.host }}</td>
                <td class="px-3 py-2.5">
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-bold scry-badge-pale-blue">
                    {{ p.state || 'active' }}
                  </span>
                </td>
                <td class="px-3 py-2.5 font-bold" :class="p.time > 10 ? 'text-rose-500' : 'scry-text-main'">
                  {{ p.time }}s
                </td>
                <td class="px-3 py-2.5 max-w-md truncate" :title="p.info">
                  {{ p.info || '(idle)' }}
                </td>
                <td class="px-3 py-2.5 text-right">
                  <button
                    @click="promptKillProcess(p)"
                    class="px-2 py-1 text-[10px] font-bold rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 hover:bg-rose-500/25 transition-colors cursor-pointer"
                  >
                    Kill Process
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Configuration Recommendations -->
      <div class="space-y-4">
        <h3 class="font-bold text-sm uppercase tracking-wider scry-text-subtle">Configuration & Health Recommendations</h3>

        <div
          v-for="(item, i) in suggestions"
          :key="i"
          class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm"
        >
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2">
              <span class="text-xs font-mono font-bold uppercase px-2 py-0.5 rounded scry-badge-pale-blue">
                {{ item.category }}
              </span>
              <h3 class="font-semibold text-base scry-text-main">{{ item.title }}</h3>
            </div>
            <span
              class="text-[10px] font-bold uppercase px-2 py-0.5 rounded"
              :class="item.severity === 'warning' ? 'scry-badge-sulphur' : (item.severity === 'success' ? 'scry-badge-glaucous' : 'scry-badge-pale-blue')"
            >
              {{ item.severity }}
            </span>
          </div>

          <p class="text-xs scry-text-muted leading-relaxed font-mono mt-1">
            {{ item.recommendation }}
          </p>
        </div>
      </div>
    </div>

    <!-- Kill Process Confirmation Modal -->
    <div v-if="processToKill" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 select-none">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-md w-full shadow-2xl space-y-4 font-mono">
        <div class="flex items-center space-x-3">
          <div class="p-2.5 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </div>
          <div>
            <h3 class="font-bold text-base scry-text-main">Terminate Process PID {{ processToKill.pid }}</h3>
            <p class="text-xs scry-text-muted mt-0.5">Connection: <strong>{{ store.currentConnection }}</strong></p>
          </div>
        </div>

        <div class="p-3 rounded-lg scry-bg-input border scry-border text-xs scry-text-main leading-relaxed">
          Are you sure you want to terminate running process <strong class="text-rose-500 font-bold">PID {{ processToKill.pid }}</strong> (User: {{ processToKill.user }})?
        </div>

        <div class="flex items-center justify-end space-x-2 pt-2 border-t scry-border">
          <button
            @click="processToKill = null"
            class="px-4 py-2 text-xs font-semibold rounded-lg border scry-border scry-text-main hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="executeKillProcess"
            class="px-5 py-2 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition-colors cursor-pointer shadow-sm"
          >
            Terminate Process
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';

const store = useConnectionStore();
const loading = ref(true);
const suggestions = ref([]);
const processes = ref([]);
const processToKill = ref(null);

const loadAll = async () => {
  loading.value = true;
  try {
    const [tRes, sRes] = await Promise.all([
      store.scryFetch('/server/tuning'),
      store.scryFetch('/server/slow-queries'),
    ]);

    if (tRes.ok) {
      const data = await tRes.json();
      suggestions.value = data.suggestions || [];
    }

    if (sRes.ok) {
      const data = await sRes.json();
      processes.value = data.processes || [];
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const promptKillProcess = (proc) => {
  processToKill.value = proc;
};

const executeKillProcess = async () => {
  if (!processToKill.value) return;
  try {
    const res = await store.scryFetch('/server/kill-process', {
      method: 'POST',
      body: JSON.stringify({ pid: processToKill.value.pid }),
    });

    if (res.ok) {
      processToKill.value = null;
      await loadAll();
    } else {
      const data = await res.json();
      alert('Failed to kill process: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    alert('Error killing process: ' + err.message);
  }
};

onMounted(loadAll);
watch(() => store.currentConnection, loadAll);
</script>

