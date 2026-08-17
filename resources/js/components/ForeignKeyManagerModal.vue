<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 select-none font-mono">
    <div class="scry-bg-card border scry-border rounded-xl p-6 max-w-3xl w-full shadow-2xl space-y-4">
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b scry-border pb-3">
        <div class="flex items-center space-x-2">
          <span class="text-xs uppercase font-bold tracking-wider scry-accent-text">Foreign Key Constraint Manager</span>
          <span class="text-xs px-2 py-0.5 rounded scry-badge-glaucous font-bold">{{ tableName }}</span>
        </div>
        <button @click="$emit('close')" class="text-xs scry-text-muted font-bold cursor-pointer hover:scry-text-main">
          ✕ Close
        </button>
      </div>

      <!-- Main Body -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h4 class="text-xs font-bold uppercase tracking-wider scry-text-subtle">Existing Foreign Keys</h4>
          <button
            @click="showCreateForm = !showCreateForm"
            class="px-2.5 py-1 text-xs font-bold rounded scry-accent-bg transition-colors cursor-pointer"
          >
            {{ showCreateForm ? 'Cancel New Key' : '+ Create Foreign Key' }}
          </button>
        </div>

        <!-- Create Foreign Key Form -->
        <div v-if="showCreateForm" class="p-4 border scry-border rounded-lg scry-bg-input space-y-3">
          <h5 class="text-xs font-bold scry-accent-text">Add Foreign Key Relationship</h5>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">Local Column</label>
              <input
                v-model="newLocalCol"
                type="text"
                placeholder="e.g. user_id"
                class="w-full scry-bg-card border scry-border rounded p-2 text-xs font-mono scry-text-main"
              />
            </div>
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">Foreign Table</label>
              <input
                v-model="newForeignTable"
                type="text"
                placeholder="e.g. users"
                class="w-full scry-bg-card border scry-border rounded p-2 text-xs font-mono scry-text-main"
              />
            </div>
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">Foreign Column</label>
              <input
                v-model="newForeignCol"
                type="text"
                placeholder="e.g. id"
                class="w-full scry-bg-card border scry-border rounded p-2 text-xs font-mono scry-text-main"
              />
            </div>
            <div>
              <label class="block scry-text-muted mb-1 font-semibold">On Delete Action</label>
              <select
                v-model="newOnDelete"
                class="w-full scry-bg-card border scry-border rounded p-2 text-xs font-mono scry-text-main"
              >
                <option value="CASCADE">CASCADE</option>
                <option value="RESTRICT">RESTRICT</option>
                <option value="SET NULL">SET NULL</option>
                <option value="NO ACTION">NO ACTION</option>
              </select>
            </div>
          </div>

          <div class="flex justify-end pt-1">
            <button
              @click="executeCreateFk"
              :disabled="!newLocalCol.trim() || !newForeignTable.trim() || !newForeignCol.trim() || isSubmitting"
              class="px-4 py-1.5 text-xs font-bold rounded scry-accent-bg disabled:opacity-50 transition-colors cursor-pointer shadow-sm"
            >
              {{ isSubmitting ? 'Creating...' : 'Execute ADD CONSTRAINT' }}
            </button>
          </div>
        </div>

        <!-- Foreign Keys List -->
        <div v-if="loading" class="py-12 text-center text-xs scry-text-muted">
          Loading foreign key constraints...
        </div>

        <div v-else-if="foreignKeys.length === 0" class="py-8 text-center text-xs scry-text-muted bg-slate-500/5 rounded-lg border scry-border">
          No foreign key constraints defined on table [{{ tableName }}].
        </div>

        <div v-else class="border scry-border rounded-lg overflow-hidden">
          <table class="w-full text-left text-xs font-mono">
            <thead class="scry-bg-header border-b scry-border scry-text-muted uppercase tracking-wider">
              <tr>
                <th class="px-4 py-2.5">Constraint Name</th>
                <th class="px-4 py-2.5">Local Column</th>
                <th class="px-4 py-2.5">Foreign Reference</th>
                <th class="px-4 py-2.5 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y scry-border-subtle scry-text-main">
              <tr v-for="fk in foreignKeys" :key="fk.constraint_name" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <td class="px-4 py-2.5 font-bold scry-accent-text">{{ fk.constraint_name }}</td>
                <td class="px-4 py-2.5">{{ fk.column_name }}</td>
                <td class="px-4 py-2.5 scry-text-muted">
                  &rarr; {{ fk.foreign_table_name }}({{ fk.foreign_column_name }})
                </td>
                <td class="px-4 py-2.5 text-right">
                  <button
                    @click="executeDropFk(fk.constraint_name)"
                    class="px-2 py-1 text-[10px] font-bold rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 hover:bg-rose-500/25 transition-colors cursor-pointer"
                  >
                    Drop FK
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useConnectionStore } from '../stores/useConnectionStore';
import { useToastStore } from '../stores/useToastStore';

const props = defineProps({
  show: Boolean,
  tableName: String,
});

const emit = defineEmits(['close']);
const store = useConnectionStore();
const toast = useToastStore();

const loading = ref(false);
const showCreateForm = ref(false);
const isSubmitting = ref(false);
const foreignKeys = ref([]);

const newLocalCol = ref('');
const newForeignTable = ref('');
const newForeignCol = ref('');
const newOnDelete = ref('CASCADE');

const fetchForeignKeys = async () => {
  if (!props.tableName) return;
  loading.value = true;
  try {
    const res = await store.scryFetch(`/tables/${props.tableName}/schema`);
    if (res.ok) {
      const data = await res.json();
      foreignKeys.value = data.foreign_keys || [];
    }
  } catch (err) {
    console.error('Failed to fetch foreign keys:', err);
  } finally {
    loading.value = false;
  }
};

const executeCreateFk = async () => {
  if (!newLocalCol.value.trim() || !newForeignTable.value.trim() || !newForeignCol.value.trim()) return;
  isSubmitting.value = true;

  try {
    const res = await store.scryFetch(`/tables/${props.tableName}/foreign-keys`, {
      method: 'POST',
      body: JSON.stringify({
        column: newLocalCol.value.trim(),
        foreign_table: newForeignTable.value.trim(),
        foreign_column: newForeignCol.value.trim(),
        on_delete: newOnDelete.value,
      }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      toast.success('Foreign key constraint created successfully.');
      newLocalCol.value = '';
      newForeignTable.value = '';
      newForeignCol.value = '';
      showCreateForm.value = false;
      await fetchForeignKeys();
    } else {
      toast.error('Failed to create foreign key: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    toast.error('Error creating foreign key: ' + err.message);
  } finally {
    isSubmitting.value = false;
  }
};

const executeDropFk = async (fkName) => {
  if (!confirm(`Are you sure you want to drop foreign key constraint [${fkName}]?`)) return;

  try {
    const res = await store.scryFetch(`/tables/${props.tableName}/foreign-keys/${fkName}`, {
      method: 'DELETE',
    });

    const data = await res.json();
    if (res.ok && data.success) {
      toast.success(`Foreign key ${fkName} dropped successfully.`);
      await fetchForeignKeys();
    } else {
      toast.error('Failed to drop foreign key: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    toast.error('Error dropping foreign key: ' + err.message);
  }
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    fetchForeignKeys();
  }
});
</script>
