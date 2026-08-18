<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold scry-text-main">Database Tables</h2>
        <p class="text-sm scry-text-muted">Inspecting database structure and storage statistics for connection <span class="font-mono scry-accent-text font-bold">[{{ currentConnection }}]</span>.</p>
      </div>

      <div class="relative w-64">
        <input
          v-model="search"
          type="text"
          placeholder="Filter tables..."
          class="w-full scry-bg-input border scry-border rounded-lg px-3 py-2 text-sm scry-text-main placeholder:scry-text-subtle focus:outline-none focus:border-pink-600 shadow-sm"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-20 scry-text-muted">
      <svg class="animate-spin h-6 w-6 mr-2 text-pink-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>
      Loading tables...
    </div>

    <!-- Table Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="table in filteredTables"
        :key="table.name"
        class="scry-bg-card border scry-border rounded-xl p-5 hover:border-pink-600/60 transition-all shadow-sm group"
      >
        <div class="flex items-start justify-between mb-3">
          <h3 class="font-mono text-base font-semibold scry-text-main group-hover:scry-accent-text transition-colors">
            {{ table.name }}
          </h3>
          <span class="text-xs font-mono px-2 py-0.5 rounded scry-badge-glaucous font-semibold">
            {{ table.size }}
          </span>
        </div>

        <div class="text-xs scry-text-muted mb-4">
          Estimated rows: <span class="font-mono scry-text-main font-semibold">{{ table.rows.toLocaleString() }}</span>
        </div>

        <div class="flex items-center space-x-2 pt-3 border-t scry-border-subtle">
          <router-link
            :to="{ name: 'data', params: { table: table.name }, query: { connection: currentConnection } }"
            class="flex-1 text-center py-1.5 text-xs font-semibold rounded-md scry-accent-bg transition-colors shadow-sm"
          >
            View Data
          </router-link>
          <router-link
            :to="{ name: 'schema', params: { table: table.name }, query: { connection: currentConnection } }"
            class="flex-1 text-center py-1.5 text-xs font-semibold rounded-md scry-badge-pale-blue transition-colors"
          >
            View Schema
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue';

const props = defineProps({ connection: String, isDark: Boolean });
const emit = defineEmits(['update-driver', 'tables-loaded', 'connections-loaded']);
const baseApiUrl = inject('baseApiUrl');

const currentConnection = ref(props.connection || '');
const tables = ref([]);
const loading = ref(true);
const search = ref('');

const filteredTables = computed(() => {
  if (!search.value) return tables.value;
  return tables.value.filter(t => t.name.toLowerCase().includes(search.value.toLowerCase()));
});

const loadTables = async (conn) => {
  loading.value = true;
  if (conn) currentConnection.value = conn;
  try {
    const connParam = currentConnection.value ? `connection=${encodeURIComponent(currentConnection.value)}` : '';
    const url = connParam ? `${baseApiUrl}/tables?${connParam}` : `${baseApiUrl}/tables`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.connection) {
      currentConnection.value = data.connection;
    }
    tables.value = data.tables || [];
    emit('update-driver', data.driver);
    emit('tables-loaded', data.tables);
    if (data.available_connections) {
      emit('connections-loaded', data.available_connections);
    }
  } catch (err) {
    console.error('Failed to fetch tables:', err);
  } finally {
    loading.value = false;
  }
};

const handleConnectionChange = (e) => {
  loadTables(e.detail);
};

onMounted(() => {
  loadTables(currentConnection.value);
  window.addEventListener('connection-changed', handleConnectionChange);
});

onUnmounted(() => {
  window.removeEventListener('connection-changed', handleConnectionChange);
});
</script>
