<script setup lang="ts">
import { useToast } from '@/composables/useToast'

const { toasts, dismissToast } = useToast()
</script>

<template>
  <div
    role="status"
    aria-live="polite"
    class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-center gap-2 sm:inset-x-auto sm:right-6 sm:bottom-6 sm:items-end"
  >
    <TransitionGroup
      enter-active-class="motion-safe:transition motion-safe:duration-200"
      enter-from-class="motion-safe:translate-y-2 opacity-0"
      leave-active-class="motion-safe:transition motion-safe:duration-150"
      leave-to-class="opacity-0"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto flex items-center gap-3 rounded-md bg-ink py-3 pr-2 pl-4 text-sm text-white shadow-lg"
      >
        <svg viewBox="0 0 20 20" class="size-4 shrink-0 text-marigold" aria-hidden="true">
          <path
            fill="none"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2.5"
            d="m4 10.5 4 4 8-9"
          />
        </svg>
        <span>{{ toast.message }}</span>
        <button
          type="button"
          class="rounded px-2 py-1 text-white/70 hover:text-white"
          aria-label="Dismiss"
          @click="dismissToast(toast.id)"
        >
          ✕
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
