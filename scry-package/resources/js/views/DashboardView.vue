<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold scry-text-main">Database Server Dashboard</h2>
        <p class="text-sm scry-text-muted">Real-time performance metrics and connection statistics for <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <button
        @click="store.fetchServerStats()"
        class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm cursor-pointer flex items-center space-x-1"
      >
        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <span>Refresh Stats</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="store.loadingStats" class="py-20 text-center scry-text-muted">
      Loading server performance metrics...
    </div>

    <div v-else class="space-y-6">
      <!-- Top Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Database Name -->
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm">
          <span class="text-xs font-semibold uppercase tracking-wider scry-text-subtle">Database Name</span>
          <div class="mt-2 text-xl font-mono font-bold scry-text-main truncate" :title="store.serverStats?.database_name">
            {{ store.serverStats?.database_name || 'N/A' }}
          </div>
          <span class="inline-block mt-2 px-2 py-0.5 text-[10px] font-bold rounded uppercase scry-badge-glaucous">
            {{ store.serverStats?.driver }}
          </span>
        </div>

        <!-- Storage Size -->
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm">
          <span class="text-xs font-semibold uppercase tracking-wider scry-text-subtle">Storage Size</span>
          <div class="mt-2 text-xl font-mono font-bold scry-accent-text">
            {{ store.serverStats?.storage_size || '0 B' }}
          </div>
          <span class="text-[11px] scry-text-muted mt-2 block">
            {{ (store.serverStats?.storage_size_bytes || 0).toLocaleString() }} bytes total
          </span>
        </div>

        <!-- Active Connections -->
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm">
          <span class="text-xs font-semibold uppercase tracking-wider scry-text-subtle">Active Connections</span>
          <div class="mt-2 text-xl font-mono font-bold text-emerald-600 dark:text-emerald-400">
            {{ store.serverStats?.active_connections ?? 0 }}
          </div>
          <span class="text-[11px] scry-text-muted mt-2 block">
            {{ store.serverStats?.idle_connections ?? 0 }} idle process(es)
          </span>
        </div>

        <!-- Total Connections -->
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm">
          <span class="text-xs font-semibold uppercase tracking-wider scry-text-subtle">Total Connections</span>
          <div class="mt-2 text-xl font-mono font-bold scry-text-main">
            {{ store.serverStats?.total_connections ?? 0 }}
          </div>
          <span class="inline-block mt-2 px-2 py-0.5 text-[10px] font-bold rounded scry-badge-pale-blue">
            Active Engine
          </span>
        </div>
      </div>

      <!-- Server Version & Details -->
      <div class="scry-bg-card border scry-border rounded-xl p-6 shadow-sm">
        <h3 class="text-sm font-semibold scry-text-main mb-3">Database Engine Information</h3>
        <div class="p-4 rounded-lg scry-bg-input border scry-border font-mono text-xs scry-text-main overflow-x-auto">
          {{ store.serverStats?.version }}
        </div>
      </div>

      <!-- Connection Switcher Panel -->
      <div class="scry-bg-card border scry-border rounded-xl p-6 shadow-sm">
        <h3 class="text-sm font-semibold scry-text-main mb-2">Switch Active Connection</h3>
        <p class="text-xs scry-text-muted mb-4">Select a database connection configured in your Laravel host application:</p>

        <div class="flex items-center space-x-3">
          <button
            v-for="conn in store.availableConnections"
            :key="conn"
            @click="store.setConnection(conn)"
            class="px-4 py-2 text-xs font-mono font-bold rounded-lg border transition-all cursor-pointer shadow-sm"
            :class="store.currentConnection === conn ? 'scry-accent-bg border-pink-600' : 'scry-bg-input scry-text-main scry-border hover:scry-border-main'"
          >
            {{ conn }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';

const store = useConnectionStore();

onMounted(() => {
  store.fetchServerStats();
});
</script>
