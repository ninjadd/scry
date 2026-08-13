<template>
  <div :class="['flex h-screen font-sans scry-bg-app scry-text-main transition-colors duration-200', isDark ? 'theme-dark' : 'theme-light']">
    <!-- Sidebar -->
    <aside class="w-64 border-r scry-border scry-bg-sidebar flex flex-col justify-between select-none">
      <div class="overflow-y-auto flex-1">
        <!-- Logo & Brand -->
        <div class="p-4 border-b scry-border flex items-center justify-between">
          <div class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-lg scry-accent-bg flex items-center justify-center font-bold text-white shadow-md">
              S
            </div>
            <div>
              <h1 class="font-bold text-base leading-none scry-text-main">Scry</h1>
              <span class="text-[11px] scry-text-muted">Database Manager</span>
            </div>
          </div>
          <div class="flex items-center space-x-1.5 px-2 py-0.5 rounded-full scry-bg-card border scry-border text-[10px] font-mono font-medium scry-text-main">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span>Live</span>
          </div>
        </div>

        <!-- Connection Switcher -->
        <div class="p-3 border-b scry-border">
          <div class="flex items-center justify-between mb-1">
            <label class="block text-[10px] uppercase font-bold tracking-wider scry-text-subtle">
              Connection
            </label>
            <span class="text-[10px] font-mono uppercase px-1.5 py-0.2 rounded scry-badge-glaucous font-bold">
              {{ connectionStore.currentConnection }}
            </span>
          </div>
          <select
            v-model="connectionStore.currentConnection"
            @change="handleConnectionChange"
            class="w-full scry-bg-input border scry-border rounded px-2.5 py-1.5 text-xs scry-text-main focus:outline-none focus:ring-2 focus:ring-pink-500/50 font-mono shadow-sm"
          >
            <option v-for="conn in connectionStore.availableConnections" :key="conn" :value="conn">
              {{ conn }} ({{ conn === 'pgsql' ? 'PostgreSQL' : (conn === 'mysql' ? 'MySQL' : (conn === 'mariadb' ? 'MariaDB' : (conn === 'sqlite' ? 'SQLite' : (conn === 'sqlsrv' ? 'SQL Server' : conn)))) }})
            </option>
          </select>
        </div>

        <!-- Navigation Links -->
        <nav class="p-3 space-y-4">
          <!-- Overview Section -->
          <div>
            <span class="px-3 text-[10px] font-bold uppercase tracking-wider scry-text-subtle">Overview</span>
            <div class="mt-1 space-y-0.5">
              <router-link
                to="/"
                class="flex items-center px-3 py-1.5 text-xs rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
                :class="$route.name === 'dashboard' || $route.name === 'dashboard-alt' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
              >
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
              </router-link>
              <router-link
                to="/tables"
                class="flex items-center px-3 py-1.5 text-xs rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
                :class="$route.name === 'tables' || $route.name === 'data' || $route.name === 'schema' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
              >
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Tables Browser
              </router-link>
            </div>
          </div>

          <!-- Visual Tools Section -->
          <div>
            <span class="px-3 text-[10px] font-bold uppercase tracking-wider scry-text-subtle">Visual Tools</span>
            <div class="mt-1 space-y-0.5">
              <router-link
                to="/qbe"
                class="flex items-center px-3 py-1.5 text-xs rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
                :class="$route.name === 'qbe' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
              >
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a2 2 0 01-2 2 2 2 0 01-2-2V4zM4 7a2 2 0 114 0v1a2 2 0 01-2 2 2 2 0 01-2-2V7zM18 7a2 2 0 114 0v1a2 2 0 01-2 2 2 2 0 01-2-2V7z"/></svg>
                QBE Query Builder
              </router-link>
              <router-link
                to="/erd"
                class="flex items-center px-3 py-1.5 text-xs rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
                :class="$route.name === 'erd' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
              >
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                ERD Visualizer
              </router-link>
            </div>
          </div>

          <!-- Queries & Search Section -->
          <div>
            <span class="px-3 text-[10px] font-bold uppercase tracking-wider scry-text-subtle">Queries & Search</span>
            <div class="mt-1 space-y-0.5">
              <router-link
                to="/query"
                class="flex items-center px-3 py-1.5 text-xs rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
                :class="$route.name === 'query' || $route.name === 'console' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
              >
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                SQL Console
              </router-link>
              <router-link
                to="/search"
                class="flex items-center px-3 py-1.5 text-xs rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
                :class="$route.name === 'search' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
              >
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Global Search
              </router-link>
            </div>
          </div>

          <!-- Maintenance & Tools Section -->
          <div>
            <span class="px-3 text-[10px] font-bold uppercase tracking-wider scry-text-subtle">Maintenance & Tools</span>
            <div class="mt-1 space-y-0.5">
              <router-link
                to="/tuning"
                class="flex items-center px-3 py-1.5 text-xs rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
                :class="$route.name === 'tuning' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
              >
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Tuning Advisor
              </router-link>
              <router-link
                to="/users"
                class="flex items-center px-3 py-1.5 text-xs rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
                :class="$route.name === 'users' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
              >
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                User Privileges
              </router-link>
              <router-link
                to="/routines"
                class="flex items-center px-3 py-1.5 text-xs rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
                :class="$route.name === 'routines' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
              >
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                Routines & Triggers
              </router-link>
              <router-link
                to="/import-export"
                class="flex items-center px-3 py-1.5 text-xs rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-pink-500/50"
                :class="$route.name === 'import-export' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
              >
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Import / Export
              </router-link>
            </div>
          </div>
        </nav>
      </div>

      <!-- Bottom Left Hand Side Dark/Light Mode Toggle -->
      <div class="p-3 border-t scry-border flex items-center justify-between">
        <button
          @click="toggleTheme"
          class="flex items-center space-x-2 text-xs font-semibold px-3 py-2 rounded-lg transition-all border scry-border shadow-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500/50"
          :class="isDark ? 'scry-badge-sulphur hover:opacity-90' : 'scry-bg-card scry-text-main hover:bg-slate-100'"
          title="Toggle Light / Dark Mode (Default: Light Mode)"
          aria-label="Toggle visual theme mode"
        >
          <!-- Sun Icon for Light Mode -->
          <svg v-if="!isDark" class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <!-- Moon Icon for Dark Mode -->
          <svg v-else class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
          <span>{{ isDark ? 'Dark Mode' : 'Light Mode' }}</span>
        </button>

        <span class="text-[10px] font-mono scry-text-subtle" title="Seasons #63 Palette">#63</span>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col overflow-hidden scry-bg-app">
      <!-- Global Top Header Bar with Breadcrumb Context -->
      <header class="px-6 py-3 border-b scry-border scry-bg-sidebar flex items-center justify-between select-none">
        <div class="flex items-center space-x-2 text-xs">
          <span class="scry-text-subtle font-medium">Scry</span>
          <span class="scry-text-subtle">/</span>
          <span class="font-semibold scry-text-main capitalize">
            {{ formatBreadcrumb($route) }}
          </span>
        </div>

        <div class="flex items-center space-x-3 text-xs">
          <div class="flex items-center space-x-1.5 px-2.5 py-1 rounded-md scry-bg-card border scry-border font-mono shadow-sm">
            <span class="scry-text-subtle">Database:</span>
            <span class="font-bold scry-accent-text">{{ connectionStore.serverStats?.database_name || connectionStore.currentConnection }}</span>
          </div>
        </div>
      </header>

      <router-view />
    </main>

    <!-- Global Toast Container -->
    <ToastContainer />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useConnectionStore } from './stores/useConnectionStore';
import ToastContainer from './components/ToastContainer.vue';

const $route = useRoute();
const connectionStore = useConnectionStore();

const savedTheme = localStorage.getItem('scry-theme') || 'light';
const isDark = ref(savedTheme === 'dark');

const formatBreadcrumb = (route) => {
  if (!route || !route.name) return 'Dashboard';
  if (route.name === 'data' && route.params?.table) return `Tables / ${route.params.table} / Data Grid`;
  if (route.name === 'schema' && route.params?.table) return `Tables / ${route.params.table} / Schema`;
  return route.name.replace('-', ' ');
};

const applyTheme = () => {
  if (isDark.value) {
    document.documentElement.classList.add('theme-dark');
    document.documentElement.classList.remove('theme-light');
  } else {
    document.documentElement.classList.add('theme-light');
    document.documentElement.classList.remove('theme-dark');
  }
};

const toggleTheme = () => {
  isDark.value = !isDark.value;
  localStorage.setItem('scry-theme', isDark.value ? 'dark' : 'light');
  applyTheme();
};

const handleConnectionChange = () => {
  connectionStore.setConnection(connectionStore.currentConnection);
};

onMounted(() => {
  applyTheme();
  connectionStore.fetchServerStats();
});
</script>

