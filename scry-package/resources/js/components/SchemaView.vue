<template>
  <div class="flex-1 p-6 overflow-y-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <div class="flex items-center space-x-3 mb-1">
          <router-link to="/" class="text-xs text-slate-400 hover:text-slate-200">&larr; Back to Tables</router-link>
          <span class="text-xs text-slate-600">/</span>
          <span class="text-xs text-indigo-400 font-mono">{{ table }}</span>
        </div>
        <h2 class="text-2xl font-bold font-mono text-slate-100">Schema: {{ table }}</h2>
      </div>

      <div class="flex space-x-2">
        <router-link
          :to="{ name: 'data', params: { table } }"
          class="px-4 py-2 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition-colors"
        >
          View Table Data &rarr;
        </router-link>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-20 text-center text-slate-500">Loading schema details...</div>

    <div v-else class="space-y-8">
      <!-- Columns Table -->
      <div class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800">
          <h3 class="font-semibold text-slate-200">Columns ({{ schema.columns?.length || 0 }})</h3>
        </div>
        <table class="w-full text-left text-xs font-mono">
          <thead class="bg-slate-900 border-b border-slate-800 text-slate-400 uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3">Column</th>
              <th class="px-5 py-3">Type</th>
              <th class="px-5 py-3">Nullable</th>
              <th class="px-5 py-3">Default</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <tr v-for="col in schema.columns" :key="col.name" class="hover:bg-slate-800/30">
              <td class="px-5 py-3 font-semibold text-indigo-300">{{ col.name }}</td>
              <td class="px-5 py-3 text-emerald-400">{{ col.type }}</td>
              <td class="px-5 py-3">
                <span :class="col.nullable === 'YES' || col.nullable === true ? 'text-amber-400' : 'text-slate-500'">
                  {{ col.nullable }}
                </span>
              </td>
              <td class="px-5 py-3 text-slate-400">{{ col.default_value ?? 'NULL' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Indexes -->
      <div class="bg-slate-900/60 border border-slate-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800">
          <h3 class="font-semibold text-slate-200">Indexes ({{ schema.indexes?.length || 0 }})</h3>
        </div>
        <table class="w-full text-left text-xs font-mono">
          <thead class="bg-slate-900 border-b border-slate-800 text-slate-400 uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3">Index Name</th>
              <th class="px-5 py-3">Column</th>
              <th class="px-5 py-3">Primary / Unique</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <tr v-for="(idx, i) in schema.indexes" :key="i" class="hover:bg-slate-800/30">
              <td class="px-5 py-3 text-slate-200">{{ idx.index_name }}</td>
              <td class="px-5 py-3 text-indigo-300">{{ idx.column_name }}</td>
              <td class="px-5 py-3">
                <span v-if="idx.is_primary" class="text-rose-400 font-bold">PRIMARY</span>
                <span v-else-if="idx.is_unique || idx.non_unique === 0" class="text-purple-400">UNIQUE</span>
                <span v-else class="text-slate-500">INDEX</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, inject } from 'vue';

const props = defineProps({ table: String });
const baseApiUrl = inject('baseApiUrl');

const schema = ref({});
const loading = ref(true);

const fetchSchema = async () => {
  loading.value = true;
  try {
    const res = await fetch(`${baseApiUrl}/tables/${props.table}/schema`);
    schema.value = await res.json();
  } catch (err) {
    console.error('Failed to load schema:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchSchema);
watch(() => props.table, fetchSchema);
</script>
