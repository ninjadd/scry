<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-3 select-none">
      <div>
        <h2 class="text-2xl font-bold scry-text-main mb-1">Server Process Monitor & Tuning Advisor</h2>
        <p class="text-sm scry-text-muted">
          Inspect active threads, terminate long-running queries, monitor connection health, and view tuning diagnostics on 
          <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Health Status Pill -->
        <div
          v-if="healthStatus"
          class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg border text-xs font-mono"
          :class="healthStatus.status === 'healthy' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-600 dark:text-rose-400'"
        >
          <span class="w-2 h-2 rounded-full animate-pulse" :class="healthStatus.status === 'healthy' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
          <span class="font-bold">{{ healthStatus.status === 'healthy' ? 'Online' : 'Degraded' }}</span>
          <span class="opacity-75">({{ healthStatus.latency_ms }}ms)</span>
        </div>

        <!-- Auto-Refresh Selector -->
        <div class="flex items-center space-x-1.5 text-xs font-mono scry-bg-input border scry-border rounded-lg p-1">
          <span class="text-slate-400 pl-1 text-[11px]">Auto-Refresh:</span>
          <select
            v-model.number="refreshIntervalSeconds"
            @change="setupPolling"
            class="scry-bg-card border scry-border rounded px-2 py-0.5 text-xs font-bold scry-accent-text focus:outline-none cursor-pointer"
          >
            <option :value="0">Off (Manual)</option>
            <option :value="3">Every 3s</option>
            <option :value="5">Every 5s</option>
            <option :value="10">Every 10s</option>
            <option :value="30">Every 30s</option>
          </select>
        </div>

        <button
          @click="loadAll"
          class="px-3.5 py-1.5 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm cursor-pointer"
        >
          Refresh Now
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !processes.length" class="py-20 text-center scry-text-muted font-mono text-xs">
      Connecting and querying active server processes...
    </div>

    <div v-else class="space-y-6">
      <!-- Active Processes & Query Cancellation Grid -->
      <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <span class="text-xs font-mono font-bold uppercase px-2 py-0.5 rounded scry-badge-glaucous">
              PROCESSLIST
            </span>
            <h3 class="font-bold text-base scry-text-main">Live Server Threads & Queries</h3>
          </div>

          <div class="flex items-center space-x-2 text-xs font-mono">
            <span class="scry-text-muted">
              <strong class="scry-accent-text">{{ processes.length }}</strong> active thread(s)
            </span>
          </div>
        </div>

        <div v-if="processes.length === 0" class="py-10 text-center text-xs scry-text-muted font-mono bg-slate-500/5 rounded-lg border scry-border">
          No non-idle queries currently executing on connection [{{ store.currentConnection }}].
        </div>

        <div v-else class="overflow-x-auto border scry-border rounded-lg">
          <table class="w-full text-left text-xs font-mono select-none">
            <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
              <tr>
                <th class="px-3 py-2.5">PID / Session ID</th>
                <th class="px-3 py-2.5">User</th>
                <th class="px-3 py-2.5">Host</th>
                <th class="px-3 py-2.5">Database</th>
                <th class="px-3 py-2.5">State</th>
                <th class="px-3 py-2.5">Duration</th>
                <th class="px-3 py-2.5">SQL Query Info</th>
                <th class="px-3 py-2.5 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y scry-border-subtle scry-text-main">
              <tr
                v-for="p in processes"
                :key="p.pid"
                class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                :class="p.time > 15 ? 'bg-rose-500/5' : (p.time > 5 ? 'bg-amber-500/5' : '')"
              >
                <td class="px-3 py-2.5 font-bold scry-accent-text">{{ p.pid }}</td>
                <td class="px-3 py-2.5">{{ p.user }}</td>
                <td class="px-3 py-2.5 scry-text-subtle">{{ p.host }}</td>
                <td class="px-3 py-2.5 scry-text-subtle">{{ p.db || '-' }}</td>
                <td class="px-3 py-2.5">
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-bold scry-badge-pale-blue">
                    {{ p.state || 'active' }}
                  </span>
                </td>
                <td class="px-3 py-2.5 font-bold">
                  <span
                    class="px-1.5 py-0.5 rounded text-[10px]"
                    :class="p.time > 15 ? 'bg-rose-500/20 text-rose-500' : (p.time > 5 ? 'bg-amber-500/20 text-amber-500' : 'scry-badge-glaucous')"
                  >
                    {{ p.time }}s
                  </span>
                </td>
                <td class="px-3 py-2.5 max-w-md truncate" :title="p.info">
                  {{ p.info || '(idle / waiting)' }}
                </td>
                <td class="px-3 py-2.5 text-right">
                  <button
                    @click="promptKillProcess(p)"
                    class="px-2.5 py-1 text-[11px] font-bold rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 hover:bg-rose-500/25 transition-colors cursor-pointer"
                    title="Terminate active database thread"
                  >
                    Kill Query
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Server Tuning & Recommendations Section -->
      <div class="space-y-4">
        <h3 class="font-bold text-sm uppercase tracking-wider scry-text-subtle">
          Automated Health & Server Tuning Advisor
        </h3>

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
              <h4 class="font-semibold text-base scry-text-main">{{ item.title }}</h4>
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

    <!-- Kill Process Modal -->
    <div v-if="processToKill" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 select-none">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-md w-full shadow-2xl space-y-4 font-mono">
        <div class="flex items-center space-x-3">
          <div class="p-2.5 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </div>
          <div>
            <h3 class="font-bold text-base scry-text-main">Terminate Process PID #{{ processToKill.pid }}</h3>
            <p class="text-xs scry-text-muted mt-0.5">Connection: <strong>{{ store.currentConnection }}</strong></p>
          </div>
        </div>

        <div class="p-3 rounded-lg scry-bg-input border scry-border text-xs scry-text-main space-y-2">
          <p>
            Are you sure you want to terminate running database thread <strong class="text-rose-500 font-bold">PID {{ processToKill.pid }}</strong> (User: {{ processToKill.user }})?
          </p>
          <div v-if="processToKill.info" class="p-2 bg-slate-900 text-slate-200 rounded text-[11px] truncate" :title="processToKill.info">
            SQL: {{ processToKill.info }}
          </div>
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
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';
import { useToastStore } from '../stores/useToastStore';

const store = useConnectionStore();
const toast = useToastStore();

const loading = ref(true);
const suggestions = ref([]);
const processes = ref([]);
const healthStatus = ref(null);
const processToKill = ref(null);

const refreshIntervalSeconds = ref(5);
let pollingTimer = null;

const loadAll = async () => {
  try {
    const [tRes, pRes, hRes] = await Promise.all([
      store.scryFetch('/server/tuning'),
      store.scryFetch('/server/processes'),
      store.scryFetch('/server/health'),
    ]);

    if (tRes.ok) {
      const data = await tRes.json();
      suggestions.value = data.suggestions || [];
    }

    if (pRes.ok) {
      const data = await pRes.json();
      processes.value = data.processes || [];
    }

    if (hRes.ok) {
      healthStatus.value = await hRes.json();
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const setupPolling = () => {
  if (pollingTimer) {
    clearInterval(pollingTimer);
    pollingTimer = null;
  }

  if (refreshIntervalSeconds.value > 0) {
    pollingTimer = setInterval(loadAll, refreshIntervalSeconds.value * 1000);
  }
};

const promptKillProcess = (proc) => {
  processToKill.value = proc;
};

const executeKillProcess = async () => {
  if (!processToKill.value) return;
  const pid = processToKill.value.pid;

  try {
    const res = await store.scryFetch(`/server/processes/${pid}`, {
      method: 'DELETE',
    });

    if (res.ok) {
      toast.success(`Process #${pid} terminated successfully.`);
      processToKill.value = null;
      await loadAll();
    } else {
      const data = await res.json();
      toast.error('Failed to kill process: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    toast.error('Error terminating process: ' + err.message);
  }
};

onMounted(() => {
  loadAll();
  setupPolling();
});

onUnmounted(() => {
  if (pollingTimer) {
    clearInterval(pollingTimer);
  }
});

watch(() => store.currentConnection, loadAll);
</script>
