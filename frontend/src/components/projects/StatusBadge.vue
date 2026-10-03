<script setup lang="ts">
import { STATUS_LABELS, type ProjectStatus } from '@/types'

defineProps<{ status: ProjectStatus }>()

const styles: Record<ProjectStatus, string> = {
  planning: 'bg-slate-100 text-slate-700',
  in_progress: 'bg-blue-50 text-blue-800',
  on_hold: 'bg-amber-50 text-amber-800',
  completed: 'bg-green-50 text-green-800',
}
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap"
    :class="styles[status]"
  >
    {{ STATUS_LABELS[status] }}
  </span>
</template>
