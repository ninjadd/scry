<template>
  <div class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 max-w-sm w-full pointer-events-none select-none font-mono text-xs">
    <transition-group
      enter-active-class="transform transition duration-300 ease-out"
      enter-from-class="translate-y-2 opacity-0 scale-95"
      enter-to-class="translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-for="t in toastStore.toasts"
        :key="t.id"
        class="pointer-events-auto p-3.5 rounded-xl border shadow-xl flex items-center justify-between space-x-3 backdrop-blur-md transition-all"
        :class="{
          'bg-emerald-950/90 border-emerald-500/40 text-emerald-200': t.type === 'success',
          'bg-rose-950/90 border-rose-500/40 text-rose-200': t.type === 'error',
          'bg-amber-950/90 border-amber-500/40 text-amber-200': t.type === 'warning',
          'bg-sky-950/90 border-sky-500/40 text-sky-200': t.type === 'info',
        }"
      >
        <div class="flex items-center space-x-2">
          <span class="font-bold text-sm">
            <span v-if="t.type === 'success'">✓</span>
            <span v-else-if="t.type === 'error'">✕</span>
            <span v-else-if="t.type === 'warning'">⚠</span>
            <span v-else>ℹ</span>
          </span>
          <span class="leading-snug">{{ t.message }}</span>
        </div>

        <button
          @click="toastStore.removeToast(t.id)"
          class="text-xs opacity-60 hover:opacity-100 cursor-pointer p-0.5"
        >
          ✕
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { useToastStore } from '../stores/useToastStore';

const toastStore = useToastStore();
</script>
