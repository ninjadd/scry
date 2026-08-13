<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold scry-text-main">Database User Accounts & Privileges</h2>
        <p class="text-sm scry-text-muted">Manage MySQL database user credentials, hosts, and granted permissions for <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <div class="flex items-center space-x-2">
        <button
          v-if="hasPrivileges"
          @click="showCreateUserModal = true"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm cursor-pointer"
        >
          Create MySQL User
        </button>
        <button
          @click="loadUsers"
          class="px-3.5 py-2 text-xs font-semibold rounded-lg border scry-border scry-bg-card scry-text-main transition-colors shadow-sm cursor-pointer"
        >
          Refresh
        </button>
      </div>
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
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y scry-border-subtle scry-text-main">
          <tr v-for="u in users" :key="u.user + u.host" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
            <td class="px-5 py-3 font-semibold scry-accent-text">{{ u.user }}</td>
            <td class="px-5 py-3 scry-text-main">{{ u.host }}</td>
            <td class="px-5 py-3 text-right">
              <button @click="openPrivilegesModal(u.user, u.host)" class="px-2.5 py-1 text-[11px] font-semibold rounded scry-badge-pale-blue cursor-pointer">
                Manage Privileges
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create User Modal -->
    <div v-if="showCreateUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showCreateUserModal = false">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <h3 class="font-bold text-base scry-text-main">Create MySQL User Account</h3>
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Username</label>
          <input v-model="newUsername" type="text" placeholder="e.g. app_reporter" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main" />
        </div>
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Host Restriction</label>
          <input v-model="newHost" type="text" placeholder="e.g. % or localhost" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main" />
        </div>
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Password</label>
          <input v-model="newPassword" type="password" placeholder="Password" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main" />
        </div>
        <div class="flex items-center justify-end space-x-2 pt-3 border-t scry-border">
          <button @click="showCreateUserModal = false" class="px-4 py-2 text-xs font-semibold rounded-lg border scry-border scry-text-main">Cancel</button>
          <button @click="submitCreateUser" class="px-4 py-2 text-xs font-semibold rounded-lg scry-accent-bg">Create User</button>
        </div>
      </div>
    </div>

    <!-- Privileges Matrix Modal -->
    <div v-if="showPrivModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showPrivModal = false">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-lg w-full shadow-2xl space-y-4">
        <h3 class="font-bold text-base scry-text-main">Manage Privileges: '{{ selectedUser }}'@'{{ selectedHost }}'</h3>
        
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Action</label>
          <select v-model="privAction" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main">
            <option value="grant">GRANT Privileges</option>
            <option value="revoke">REVOKE Privileges</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-2">Select Privileges</label>
          <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 border scry-border rounded scry-bg-input">
            <label v-for="p in availablePrivs" :key="p" class="flex items-center space-x-2 text-xs font-mono scry-text-main cursor-pointer">
              <input type="checkbox" :value="p" v-model="selectedPrivs" />
              <span>{{ p }}</span>
            </label>
          </div>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-3 border-t scry-border">
          <button @click="showPrivModal = false" class="px-4 py-2 text-xs font-semibold rounded-lg border scry-border scry-text-main">Cancel</button>
          <button @click="submitPrivileges" class="px-4 py-2 text-xs font-semibold rounded-lg scry-accent-bg">Apply Privileges</button>
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
const hasPrivileges = ref(false);
const users = ref([]);

const showCreateUserModal = ref(false);
const newUsername = ref('');
const newHost = ref('%');
const newPassword = ref('');

const showPrivModal = ref(false);
const selectedUser = ref('');
const selectedHost = ref('');
const privAction = ref('grant');
const availablePrivs = ['ALL PRIVILEGES', 'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'ALTER'];
const selectedPrivs = ref(['SELECT', 'INSERT', 'UPDATE', 'DELETE']);

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

const submitCreateUser = async () => {
  if (!newUsername.value || !newPassword.value) return;

  try {
    const res = await store.scryFetch('/users', {
      method: 'POST',
      body: JSON.stringify({ username: newUsername.value, host: newHost.value, password: newPassword.value }),
    });

    if (res.ok) {
      showCreateUserModal.value = false;
      newUsername.value = '';
      newPassword.value = '';
      loadUsers();
    }
  } catch (err) {
    alert('Failed to create user: ' + err.message);
  }
};

const openPrivilegesModal = (user, host) => {
  selectedUser.value = user;
  selectedHost.value = host;
  showPrivModal.value = true;
};

const submitPrivileges = async () => {
  if (selectedPrivs.value.length === 0) return;

  try {
    const res = await store.scryFetch('/users/privileges', {
      method: 'POST',
      body: JSON.stringify({
        username: selectedUser.value,
        host: selectedHost.value,
        action: privAction.value,
        privileges: selectedPrivs.value,
      }),
    });

    if (res.ok) {
      showPrivModal.value = false;
      alert('Privileges updated successfully.');
    }
  } catch (err) {
    alert('Failed to apply privileges: ' + err.message);
  }
};

onMounted(loadUsers);
watch(() => store.currentConnection, loadUsers);
</script>
