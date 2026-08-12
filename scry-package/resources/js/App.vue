<template>
  <div class="flex h-screen bg-slate-950 text-slate-100 font-sans">
    <!-- Sidebar -->
    <aside class="w-64 border-r border-slate-800 bg-slate-900/50 flex flex-col">
      <!-- Logo & Brand -->
      <div class="p-4 border-b border-slate-800 flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white shadow-lg shadow-indigo-500/30">
            S
          </div>
          <div>
            <h1 class="font-bold text-slate-100 leading-none">Scry</h1>
            <span class="text-xs text-slate-400">Database Manager</span>
          </div>
        </div>
        <span 
          v-if="driver"
          class="px-2 py-0.5 text-[10px] uppercase font-bold tracking-wider rounded border"
          :class="driver === 'pgsql' ? 'bg-blue-500/10 text-blue-400 border-blue-500/30' : 'bg-amber-500/10 text-amber-400 border-amber-500/30'"
        >
          {{ driver }}
        </span>
      </div>

      <!-- Navigation Links -->
      <nav class="p-3 space-y-1">
        <router-link
          to="/"
          class="flex items-center px-3 py-2 text-sm rounded-lg font-medium transition-colors"
          :class="$route.name === 'tables' ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'"
        >
          <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
          </svg>
          Tables Overview
        </router-link>

        <router-link
          to="/console"
          class="flex items-center px-3 py-2 text-sm rounded-lg font-medium transition-colors"
          :class="$route.name === 'console' ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'"
        >
          <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          SQL Console
        </router-link>
      </nav>

      <!-- Tables Quick List -->
      <div class="mt-4 px-3 flex-1 overflow-y-auto">
        <h3 class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
          Tables ({{ tables.length }})
        </h3>
        <div class="space-y-0.5">
          <router-link
            v-for="table in tables"
            :key="table.name"
            :to="{ name: 'data', params: { table: table.name } }"
            class="flex items-center justify-between px-3 py-1.5 text-xs rounded-md truncate transition-colors"
            :class="$route.params.table === table.name ? 'bg-slate-800 text-slate-100 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'"
          >
            <span class="truncate">{{ table.name }}</span>
            <span class="text-[10px] text-slate-500 font-mono">{{ table.rows }}</span>
          </router-link>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col overflow-hidden bg-slate-950">
      <router-view @update-driver="setDriver" @tables-loaded="setTables" />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const driver = ref('');
const tables = ref([]);

const setDriver = (val) => { driver.value = val; };
const setTables = (val) => { tables.value = val; };
</script>
