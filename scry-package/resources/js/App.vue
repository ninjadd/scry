<template>
  <div :class="['flex h-screen font-sans scry-bg-app scry-text-main transition-colors duration-200', isDark ? 'theme-dark' : 'theme-light']">
    <!-- Sidebar -->
    <aside class="w-64 border-r scry-border scry-bg-sidebar flex flex-col justify-between select-none">
      <div>
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
        </div>

        <!-- Connection Switcher -->
        <div class="p-3 border-b scry-border">
          <label class="block text-[10px] uppercase font-bold tracking-wider mb-1 scry-text-subtle">
            Connection
          </label>
          <select
            v-model="selectedConnection"
            @change="changeConnection"
            class="w-full scry-bg-input border scry-border rounded px-2.5 py-1.5 text-xs scry-text-main focus:outline-none focus:border-pink-600 font-mono shadow-sm"
          >
            <option v-for="conn in availableConnections" :key="conn" :value="conn">
              {{ conn }} ({{ conn === 'pgsql' ? 'PostgreSQL' : (conn === 'mysql' ? 'MySQL' : conn) }})
            </option>
          </select>
        </div>

        <!-- Navigation Links -->
        <nav class="p-3 space-y-1">
          <router-link
            to="/"
            class="flex items-center px-3 py-2 text-sm rounded-lg font-medium transition-colors"
            :class="$route.name === 'tables' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
          >
            <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            Tables Overview
          </router-link>

          <router-link
            to="/console"
            class="flex items-center px-3 py-2 text-sm rounded-lg font-medium transition-colors"
            :class="$route.name === 'console' ? 'scry-accent-bg font-semibold shadow-sm' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
          >
            <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            SQL Console
          </router-link>
        </nav>

        <!-- Tables Quick List -->
        <div class="mt-2 px-3 overflow-y-auto max-h-[calc(100vh-290px)]">
          <h3 class="px-3 text-xs font-semibold scry-text-subtle uppercase tracking-wider mb-2">
            Tables ({{ tables.length }})
          </h3>
          <div class="space-y-0.5">
            <router-link
              v-for="table in tables"
              :key="table.name"
              :to="{ name: 'data', params: { table: table.name }, query: { connection: selectedConnection } }"
              class="flex items-center justify-between px-3 py-1.5 text-xs rounded-md truncate transition-colors"
              :class="$route.params.table === table.name ? 'scry-bg-card scry-text-main font-semibold border scry-border' : 'scry-text-muted hover:scry-text-main hover:scry-bg-card'"
            >
              <span class="truncate">{{ table.name }}</span>
              <span class="text-[10px] font-mono opacity-80">{{ table.rows }}</span>
            </router-link>
          </div>
        </div>
      </div>

      <!-- Bottom Left Hand Side Dark/Light Mode Toggle -->
      <div class="p-3 border-t scry-border flex items-center justify-between">
        <button
          @click="toggleTheme"
          class="flex items-center space-x-2 text-xs font-semibold px-3 py-2 rounded-lg transition-all border scry-border shadow-sm cursor-pointer"
          :class="isDark ? 'scry-badge-sulphur hover:opacity-90' : 'scry-bg-card scry-text-main hover:bg-slate-100'"
          title="Toggle Light / Dark Mode (Default: Light Mode)"
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
      <router-view
        :connection="selectedConnection"
        :is-dark="isDark"
        @update-driver="setDriver"
        @tables-loaded="setTables"
        @connections-loaded="setConnections"
      />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const driver = ref('');
const tables = ref([]);
const selectedConnection = ref('pgsql');
const availableConnections = ref(['pgsql', 'mysql']);

// Default to Light Mode as requested
const savedTheme = localStorage.getItem('scry-theme') || 'light';
const isDark = ref(savedTheme === 'dark');

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

onMounted(() => {
  applyTheme();
});

const setDriver = (val) => { driver.value = val; };
const setTables = (val) => { tables.value = val; };
const setConnections = (val) => {
  if (val && val.length > 0) availableConnections.value = val;
};

const changeConnection = () => {
  window.dispatchEvent(new CustomEvent('connection-changed', { detail: selectedConnection.value }));
};
</script>
