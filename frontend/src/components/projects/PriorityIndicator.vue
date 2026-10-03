<script setup lang="ts">
import { computed } from 'vue'
import { PRIORITY_LABELS, type ProjectPriority } from '@/types'

const props = defineProps<{ priority: ProjectPriority }>()

const BAR_HEIGHTS = ['h-1.5', 'h-2.5', 'h-3.5']
const level = computed(() => ({ low: 1, medium: 2, high: 3 })[props.priority])
const fill = computed(() => (props.priority === 'high' ? 'bg-orange-600' : 'bg-ink'))
</script>

<template>
  <span class="inline-flex items-center gap-2 text-sm whitespace-nowrap">
    <span class="inline-flex items-end gap-0.5" aria-hidden="true">
      <span
        v-for="(height, index) in BAR_HEIGHTS"
        :key="height"
        class="w-1 rounded-sm"
        :class="[height, index < level ? fill : 'bg-rule']"
      />
    </span>
    <span :class="{ 'font-semibold text-orange-700': priority === 'high' }">
      {{ PRIORITY_LABELS[priority] }}
    </span>
  </span>
</template>
