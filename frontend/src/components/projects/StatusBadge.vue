<script setup lang="ts">
import { STATUS_LABELS, type ProjectStatus } from '@/types'

defineProps<{ status: ProjectStatus }>()

const styles: Record<ProjectStatus, string> = {
  planning: 'bg-slate-100 text-slate-700 before:bg-slate-500',
  in_progress: 'bg-blue-50 text-blue-800 before:bg-blue-600',
  on_hold: 'bg-amber-50 text-amber-800 before:bg-amber-500',
  completed: 'bg-green-50 text-green-800 before:bg-green-600',
}
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap before:size-1.5 before:rounded-full before:content-['']"
    :class="styles[status]"
  >
    {{ STATUS_LABELS[status] }}
  </span>
</template>
