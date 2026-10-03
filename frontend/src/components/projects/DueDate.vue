<script setup lang="ts">
import { computed } from 'vue'
import type { Project } from '@/types'
import { formatDate } from '@/utils/dates'
import { daysOverdue } from '@/utils/projects'

const props = defineProps<{ project: Project }>()

const overdueDays = computed(() => daysOverdue(props.project))
</script>

<template>
  <span class="flex flex-col tabular-nums">
    <span :class="{ 'font-semibold text-alert': overdueDays > 0 }">
      {{ formatDate(project.due_date) }}
    </span>
    <span v-if="overdueDays > 0" class="text-xs text-alert">
      {{ overdueDays === 1 ? '1 day overdue' : `${overdueDays} days overdue` }}
    </span>
  </span>
</template>
