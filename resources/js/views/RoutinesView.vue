<template>
  <div class="flex-1 p-6 overflow-y-auto scry-bg-app">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold scry-text-main">Stored Procedures, Functions & Triggers</h2>
        <p class="text-sm scry-text-muted">Inspect and manage database routines and trigger triggers for <span class="font-mono scry-accent-text font-bold">[{{ store.currentConnection }}]</span>.</p>
      </div>

      <div class="flex items-center space-x-2">
        <button @click="showRoutineModal = true" class="px-3 py-1.5 text-xs font-semibold rounded-lg scry-accent-bg cursor-pointer">
          + Create Procedure / Function
        </button>
        <button @click="showTriggerModal = true" class="px-3 py-1.5 text-xs font-semibold rounded-lg scry-badge-pale-blue cursor-pointer">
          + Create Trigger
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-20 text-center scry-text-muted">
      Loading routines and triggers...
    </div>

    <div v-else class="space-y-6">
      <!-- Stored Procedures & Functions -->
      <div class="scry-bg-card border scry-border rounded-xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b scry-border scry-bg-header flex items-center justify-between">
          <h3 class="font-semibold text-sm scry-text-main">Stored Procedures & Functions ({{ procedures.length }})</h3>
        </div>
        <table class="w-full text-left text-xs font-mono">
          <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3">Routine Name</th>
              <th class="px-5 py-3">Type</th>
              <th class="px-5 py-3">Return Type</th>
            </tr>
          </thead>
          <tbody class="divide-y scry-border-subtle scry-text-main">
            <tr v-for="p in procedures" :key="p.name" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <td class="px-5 py-3 font-semibold scry-accent-text">{{ p.name }}</td>
              <td class="px-5 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold scry-badge-glaucous">{{ p.type }}</span></td>
              <td class="px-5 py-3 scry-text-muted">{{ p.return_type || 'N/A' }}</td>
            </tr>
            <tr v-if="procedures.length === 0">
              <td colspan="3" class="px-5 py-6 text-center scry-text-muted text-xs">No stored procedures or functions found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Triggers -->
      <div class="scry-bg-card border scry-border rounded-xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b scry-border scry-bg-header flex items-center justify-between">
          <h3 class="font-semibold text-sm scry-text-main">Triggers ({{ triggers.length }})</h3>
        </div>
        <table class="w-full text-left text-xs font-mono">
          <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3">Trigger Name</th>
              <th class="px-5 py-3">Timing</th>
              <th class="px-5 py-3">Event</th>
              <th class="px-5 py-3">Table</th>
            </tr>
          </thead>
          <tbody class="divide-y scry-border-subtle scry-text-main">
            <tr v-for="tr in triggers" :key="tr.name" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <td class="px-5 py-3 font-semibold scry-accent-text">{{ tr.name }}</td>
              <td class="px-5 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold scry-badge-sulphur">{{ tr.timing }}</span></td>
              <td class="px-5 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold scry-badge-pale-blue">{{ tr.event }}</span></td>
              <td class="px-5 py-3 scry-text-main">{{ tr.table_name || tr.table }}</td>
            </tr>
            <tr v-if="triggers.length === 0">
              <td colspan="4" class="px-5 py-6 text-center scry-text-muted text-xs">No database triggers found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Routine Modal -->
    <div v-if="showRoutineModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showRoutineModal = false">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-lg w-full shadow-2xl space-y-4">
        <h3 class="font-bold text-base scry-text-main">Create Stored Procedure / Function</h3>
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Routine Name</label>
          <input v-model="routineName" type="text" placeholder="e.g. GetActiveUserCount" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main" />
        </div>
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Type</label>
          <select v-model="routineType" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main">
            <option value="PROCEDURE">PROCEDURE</option>
            <option value="FUNCTION">FUNCTION</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Routine DDL Body</label>
          <textarea v-model="routineBody" rows="6" placeholder="CREATE PROCEDURE GetActiveUserCount()..." class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-accent-text"></textarea>
        </div>
        <div class="flex items-center justify-end space-x-2 pt-3 border-t scry-border">
          <button @click="showRoutineModal = false" class="px-4 py-2 text-xs font-semibold rounded-lg border scry-border scry-text-main">Cancel</button>
          <button @click="submitRoutine" class="px-4 py-2 text-xs font-semibold rounded-lg scry-accent-bg">Create Routine</button>
        </div>
      </div>
    </div>

    <!-- Create Trigger Modal -->
    <div v-if="showTriggerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="showTriggerModal = false">
      <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-lg w-full shadow-2xl space-y-4">
        <h3 class="font-bold text-base scry-text-main">Create Trigger</h3>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold scry-text-muted mb-1">Trigger Name</label>
            <input v-model="trigName" type="text" placeholder="trg_audit_users" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main" />
          </div>
          <div>
            <label class="block text-xs font-semibold scry-text-muted mb-1">Target Table</label>
            <input v-model="trigTable" type="text" placeholder="users" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main" />
          </div>
          <div>
            <label class="block text-xs font-semibold scry-text-muted mb-1">Timing</label>
            <select v-model="trigTiming" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main">
              <option value="BEFORE">BEFORE</option>
              <option value="AFTER">AFTER</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold scry-text-muted mb-1">Event</label>
            <select v-model="trigEvent" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-text-main">
              <option value="INSERT">INSERT</option>
              <option value="UPDATE">UPDATE</option>
              <option value="DELETE">DELETE</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold scry-text-muted mb-1">Trigger Body SQL</label>
          <textarea v-model="trigBody" rows="4" placeholder="SET NEW.updated_at = NOW();" class="w-full scry-bg-input border scry-border rounded p-2 text-xs font-mono scry-accent-text"></textarea>
        </div>
        <div class="flex items-center justify-end space-x-2 pt-3 border-t scry-border">
          <button @click="showTriggerModal = false" class="px-4 py-2 text-xs font-semibold rounded-lg border scry-border scry-text-main">Cancel</button>
          <button @click="submitTrigger" class="px-4 py-2 text-xs font-semibold rounded-lg scry-accent-bg">Create Trigger</button>
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
const procedures = ref([]);
const triggers = ref([]);

const showRoutineModal = ref(false);
const routineName = ref('');
const routineType = ref('PROCEDURE');
const routineBody = ref('');

const showTriggerModal = ref(false);
const trigName = ref('');
const trigTable = ref('');
const trigTiming = ref('BEFORE');
const trigEvent = ref('INSERT');
const trigBody = ref('SET NEW.updated_at = NOW();');

const loadRoutines = async () => {
  loading.value = true;
  try {
    const [procRes, trigRes] = await Promise.all([
      store.scryFetch('/procedures'),
      store.scryFetch('/triggers'),
    ]);

    if (procRes.ok) {
      const data = await procRes.json();
      procedures.value = data.procedures || [];
    }
    if (trigRes.ok) {
      const data = await trigRes.json();
      triggers.value = data.triggers || [];
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const submitRoutine = async () => {
  if (!routineName.value || !routineBody.value) return;

  try {
    const res = await store.scryFetch('/routines', {
      method: 'POST',
      body: JSON.stringify({ name: routineName.value, type: routineType.value, body: routineBody.value }),
    });
    if (res.ok) {
      showRoutineModal.value = false;
      routineName.value = '';
      routineBody.value = '';
      loadRoutines();
    }
  } catch (err) {
    alert('Failed to create routine: ' + err.message);
  }
};

const submitTrigger = async () => {
  if (!trigName.value || !trigTable.value || !trigBody.value) return;

  try {
    const res = await store.scryFetch('/triggers', {
      method: 'POST',
      body: JSON.stringify({ name: trigName.value, table: trigTable.value, timing: trigTiming.value, event: trigEvent.value, body: trigBody.value }),
    });
    if (res.ok) {
      showTriggerModal.value = false;
      trigName.value = '';
      trigTable.value = '';
      loadRoutines();
    }
  } catch (err) {
    alert('Failed to create trigger: ' + err.message);
  }
};

onMounted(loadRoutines);
watch(() => store.currentConnection, loadRoutines);
</script>
