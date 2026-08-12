<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold scry-text-main">Database User Accounts & Privileges</h2>
        <p class="text-sm scry-text-muted">Manage MySQL database user credentials, hosts, and granted permissions for <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <button
        @click="loadUsers"
        class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm cursor-pointer"
      >
        Refresh Users
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-20 text-center scry-text-muted">
      Checking user privileges...
    </div>

    <!-- Privilege Warning if elevated permissions are missing -->
    <div v-else-if="!hasPrivileges" class="scry-bg-card border scry-border rounded-xl p-6 shadow-sm">
      <div class="flex items-start space-x-3">
        <div class="p-2 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold">
          !
        </div>
        <div>
          <h3 class="font-semibold text-base scry-text-main">Elevated User Management Privileges Not Granted</h3>
          <p class="text-xs scry-text-muted mt-1 leading-relaxed">
            The database user configured for connection <strong class="font-mono scry-accent-text">[{{ store.currentConnection }}]</strong> does not currently hold elevated MySQL administration privileges (<code class="scry-badge-sulphur px-1">GRANT OPTION</code> or <code class="scry-badge-sulphur px-1">CREATE USER</code>).
          </p>
          <div class="mt-4 p-3 rounded-lg scry-bg-input border scry-border text-xs font-mono scry-text-main">
            GRANT ALL PRIVILEGES ON *.* TO '{{ store.serverStats?.database_name || 'user' }}'@'%' WITH GRANT OPTION;
          </div>
          <p class="text-[11px] scry-text-subtle mt-3">
            Note: All other Scry features (table schema inspection, CRUD operations, raw SQL runner, ERD visualizer, and export tools) remain fully functional without elevated privileges.
          </p>
        </div>
      </div>
    </div>

    <!-- Users Grid -->
    <div v-else class="scry-bg-card border scry-border rounded-xl overflow-hidden shadow-sm">
      <div class="px-5 py-4 border-b scry-border scry-bg-header flex items-center justify-between">
        <h3 class="font-semibold text-sm scry-text-main">MySQL Users ({{ users.length }})</h3>
      </div>
      <table class="w-full text-left text-xs font-mono">
        <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
          <tr>
            <th class="px-5 py-3">User</th>
            <th class="px-5 py-3">Host</th>
            <th class="px-5 py-3">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y scry-border-subtle scry-text-main">
          <tr v-for="u in users" :key="u.user + u.host" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
            <td class="px-5 py-3 font-semibold scry-accent-text">{{ u.user }}</td>
            <td class="px-5 py-3 scry-text-main">{{ u.host }}</td>
            <td class="px-5 py-3">
              <span class="px-2 py-0.5 text-[10px] font-bold rounded scry-badge-glaucous">Active User</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';

const store = useConnectionStore();
const loading = ref(true);
const hasPrivileges = ref(false);
const users = ref([]);

const loadUsers = async () => {
  loading.value = true;
  try {
    const res = await store.scryFetch('/users');
    if (res.ok) {
      const data = await res.json();
      hasPrivileges.value = data.has_privileges;
      users.value = data.users || [];
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

onMounted(loadUsers);
watch(() => store.currentConnection, loadUsers);
</script>
