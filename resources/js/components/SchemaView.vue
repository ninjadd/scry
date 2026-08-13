<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <div class="flex items-center space-x-3 mb-1">
          <router-link to="/" class="text-xs scry-text-muted hover:scry-text-main">&larr; Back to Tables</router-link>
          <span class="text-xs scry-text-subtle">/</span>
          <span class="text-xs scry-accent-text font-mono font-bold">{{ table }}</span>
        </div>
        <h2 class="text-2xl font-bold font-mono scry-text-main">Schema: {{ table }}</h2>
      </div>

      <div class="flex space-x-2">
        <router-link
          :to="{ name: 'data', params: { table }, query: { connection } }"
          class="px-4 py-2 text-xs font-semibold rounded-lg scry-accent-bg transition-colors shadow-sm"
        >
          View Table Data &rarr;
        </router-link>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-20 text-center scry-text-muted">Loading schema details...</div>

    <div v-else class="space-y-8">
      <!-- Columns Table -->
      <div class="scry-bg-card border scry-border rounded-xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b scry-border scry-bg-header">
          <h3 class="font-semibold text-sm scry-text-main">Columns ({{ schema.columns?.length || 0 }})</h3>
        </div>
        <table class="w-full text-left text-xs font-mono">
          <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3">Column</th>
              <th class="px-5 py-3">Type</th>
              <th class="px-5 py-3">Nullable</th>
              <th class="px-5 py-3">Default</th>
            </tr>
          </thead>
          <tbody class="divide-y scry-border-subtle scry-text-main">
            <tr v-for="col in schema.columns" :key="col.name" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <td class="px-5 py-3 font-semibold scry-accent-text flex items-center space-x-2">
                <span>{{ col.name }}</span>
                <span v-if="col.is_primary" class="text-[9px] font-sans font-bold uppercase px-1.5 py-0.5 rounded scry-badge-sulphur">PK</span>
                <span v-if="col.is_foreign_key" class="text-[9px] font-sans font-bold uppercase px-1.5 py-0.5 rounded scry-badge-pale-blue">FK</span>
              </td>
              <td class="px-5 py-3 font-bold scry-badge-glaucous inline-block my-1.5 rounded px-2 py-0.5">{{ col.full_type || col.data_type }}</td>
              <td class="px-5 py-3">
                <span :class="col.nullable === 'YES' || col.nullable === true ? 'scry-text-main font-semibold' : 'scry-text-subtle'">
                  {{ col.nullable ? 'YES' : 'NO' }}
                </span>
              </td>
              <td class="px-5 py-3 scry-text-muted">{{ col.default_value ?? 'NULL' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Indexes -->
      <div class="scry-bg-card border scry-border rounded-xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b scry-border scry-bg-header">
          <h3 class="font-semibold text-sm scry-text-main">Indexes ({{ schema.indexes?.length || 0 }})</h3>
        </div>
        <table class="w-full text-left text-xs font-mono">
          <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3">Index Name</th>
              <th class="px-5 py-3">Column</th>
              <th class="px-5 py-3">Type</th>
            </tr>
          </thead>
          <tbody class="divide-y scry-border-subtle scry-text-main">
            <tr v-for="(idx, i) in schema.indexes" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <td class="px-5 py-3 font-semibold scry-text-main">{{ idx.index_name }}</td>
              <td class="px-5 py-3 scry-accent-text font-semibold">{{ idx.column_name }}</td>
              <td class="px-5 py-3">
                <span v-if="idx.is_primary" class="font-bold px-2 py-0.5 rounded scry-badge-sulphur">PRIMARY</span>
                <span v-else-if="idx.is_unique || idx.non_unique === 0" class="font-semibold px-2 py-0.5 rounded scry-badge-pale-blue">UNIQUE</span>
                <span v-else class="scry-text-subtle">INDEX</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Foreign Keys -->
      <div v-if="schema.foreign_keys?.length" class="scry-bg-card border scry-border rounded-xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b scry-border scry-bg-header">
          <h3 class="font-semibold text-sm scry-text-main">Foreign Keys ({{ schema.foreign_keys.length }})</h3>
        </div>
        <table class="w-full text-left text-xs font-mono">
          <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3">Constraint</th>
              <th class="px-5 py-3">Column</th>
              <th class="px-5 py-3">Foreign Table</th>
              <th class="px-5 py-3">Foreign Column</th>
            </tr>
          </thead>
          <tbody class="divide-y scry-border-subtle scry-text-main">
            <tr v-for="(fk, i) in schema.foreign_keys" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <td class="px-5 py-3 scry-text-muted">{{ fk.constraint_name }}</td>
              <td class="px-5 py-3 scry-accent-text font-semibold">{{ fk.column_name }}</td>
              <td class="px-5 py-3 font-semibold text-emerald-600 dark:text-emerald-400">{{ fk.foreign_table_name }}</td>
              <td class="px-5 py-3 font-semibold">{{ fk.foreign_column_name }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, inject } from 'vue';

const props = defineProps({ table: String, connection: String, isDark: Boolean });
const baseApiUrl = inject('baseApiUrl');

const schema = ref({});
const loading = ref(true);

const fetchSchema = async () => {
  loading.value = true;
  try {
    const connParam = props.connection ? `?connection=${props.connection}` : '';
    const res = await fetch(`${baseApiUrl}/tables/${props.table}/schema${connParam}`);
    schema.value = await res.json();
  } catch (err) {
    console.error('Failed to load schema:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchSchema);
watch(() => props.table, fetchSchema);
watch(() => props.connection, fetchSchema);
</script>
