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
        class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm cursor-pointer flex items-center space-x-1 focus:outline-none focus:ring-2 focus:ring-pink-500/50"
      >
        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <span>Refresh Stats</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="store.loadingStats" class="py-20 text-center scry-text-muted font-mono text-xs">
      Loading server performance metrics...
    </div>

    <div v-else class="space-y-6">
      <!-- Top Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Database Name -->
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider scry-text-subtle">Database Name</span>
            <div class="p-2 rounded-lg scry-badge-glaucous">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
            </div>
          </div>
          <div class="mt-3">
            <div class="text-xl font-mono font-bold scry-text-main truncate" :title="store.serverStats?.database_name">
              {{ store.serverStats?.database_name || 'N/A' }}
            </div>
            <span class="inline-block mt-2 px-2 py-0.5 text-[10px] font-bold rounded uppercase scry-badge-glaucous">
              {{ store.serverStats?.driver }} Engine
            </span>
          </div>
        </div>

        <!-- Storage Size -->
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider scry-text-subtle">Storage Size</span>
            <div class="p-2 rounded-lg scry-badge-pale-blue">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
          </div>
          <div class="mt-3">
            <div class="text-xl font-mono font-bold scry-accent-text">
              {{ store.serverStats?.storage_size || '0 B' }}
            </div>
            <span class="text-[11px] scry-text-muted mt-1 block font-mono">
              {{ (store.serverStats?.storage_size_bytes || 0).toLocaleString() }} bytes total
            </span>
          </div>
        </div>

        <!-- Active Connections -->
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider scry-text-subtle">Active Connections</span>
            <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
          </div>
          <div class="mt-3">
            <div class="text-xl font-mono font-bold text-emerald-600 dark:text-emerald-400">
              {{ store.serverStats?.active_connections ?? 0 }}
            </div>
            <span class="text-[11px] scry-text-muted mt-1 block">
              {{ store.serverStats?.idle_connections ?? 0 }} idle process(es)
            </span>
          </div>
        </div>

        <!-- Total Connections -->
        <div class="scry-bg-card border scry-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider scry-text-subtle">Total Processes</span>
            <div class="p-2 rounded-lg scry-badge-sulphur">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m14-6h2m-2 6h2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            </div>
          </div>
          <div class="mt-3">
            <div class="text-xl font-mono font-bold scry-text-main">
              {{ store.serverStats?.total_connections ?? 0 }}
            </div>
            <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold rounded scry-badge-pale-blue">
              Active Engine
            </span>
          </div>
        </div>
      </div>

      <!-- Quick Developer Shortcuts -->
      <div class="scry-bg-card border scry-border rounded-xl p-6 shadow-sm">
        <h3 class="text-sm font-semibold scry-text-main flex items-center space-x-2 mb-3">
          <span>Quick Actions</span>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <router-link
            to="/tables"
            class="p-3 border scry-border rounded-lg scry-bg-input hover:border-pink-600 transition-all flex items-center space-x-3 group"
          >
            <div class="p-2 rounded scry-badge-glaucous group-hover:scale-105 transition-transform">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            </div>
            <div>
              <div class="text-xs font-bold scry-text-main">Browse Tables</div>
              <div class="text-[11px] scry-text-muted">Inspect schemas & rows</div>
            </div>
          </router-link>

          <router-link
            to="/query"
            class="p-3 border scry-border rounded-lg scry-bg-input hover:border-pink-600 transition-all flex items-center space-x-3 group"
          >
            <div class="p-2 rounded scry-badge-pale-blue group-hover:scale-105 transition-transform">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <div class="text-xs font-bold scry-text-main">SQL Console</div>
              <div class="text-[11px] scry-text-muted">Execute raw queries</div>
            </div>
          </router-link>

          <router-link
            to="/import-export"
            class="p-3 border scry-border rounded-lg scry-bg-input hover:border-pink-600 transition-all flex items-center space-x-3 group"
          >
            <div class="p-2 rounded scry-badge-sulphur group-hover:scale-105 transition-transform">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <div>
              <div class="text-xs font-bold scry-text-main">Export Data</div>
              <div class="text-[11px] scry-text-muted">CSV, SQL, PDF, JSON</div>
            </div>
          </router-link>
        </div>
      </div>

      <!-- Server Version & Details Grid -->
      <div class="scry-bg-card border scry-border rounded-xl p-6 shadow-sm">
        <h3 class="text-sm font-semibold scry-text-main mb-3">Database Engine Metadata</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
          <div class="p-3 rounded-lg scry-bg-input border scry-border font-mono text-xs">
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Driver</span>
            <span class="font-bold scry-text-main">{{ store.serverStats?.driver || 'N/A' }}</span>
          </div>
          <div class="p-3 rounded-lg scry-bg-input border scry-border font-mono text-xs">
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Connection State</span>
            <span class="font-bold text-emerald-500">Connected</span>
          </div>
          <div class="p-3 rounded-lg scry-bg-input border scry-border font-mono text-xs sm:col-span-2">
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Server Build Version</span>
            <span class="font-semibold scry-text-main truncate block" :title="store.serverStats?.version">{{ store.serverStats?.version || 'N/A' }}</span>
          </div>
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
            class="px-4 py-2 text-xs font-mono font-bold rounded-lg border transition-all cursor-pointer shadow-sm focus:outline-none focus:ring-2 focus:ring-pink-500/50"
            :class="store.currentConnection === conn ? 'scry-accent-bg border-pink-600' : 'scry-bg-input scry-text-main scry-border hover:scry-border-main'"
          >
            {{ conn }} ({{ conn === 'pgsql' ? 'PostgreSQL' : (conn === 'mysql' ? 'MySQL' : (conn === 'mariadb' ? 'MariaDB' : (conn === 'sqlite' ? 'SQLite' : (conn === 'sqlsrv' ? 'SQL Server' : conn)))) }})
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
