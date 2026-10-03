<script setup lang="ts">
import DueDate from '@/components/projects/DueDate.vue'
import PriorityIndicator from '@/components/projects/PriorityIndicator.vue'
import StatusBadge from '@/components/projects/StatusBadge.vue'
import type { Project } from '@/types'
import { formatDate } from '@/utils/dates'

defineProps<{ projects: Project[] }>()

const emit = defineEmits<{
  edit: [project: Project]
  delete: [project: Project]
}>()

const actionClass = 'rounded-md px-2 py-1 text-sm font-semibold underline-offset-2 hover:underline'
</script>

<template>
  <!-- Wide screens: a scannable table -->
  <table class="hidden w-full text-left md:table">
    <thead class="border-b border-rule text-sm text-muted">
      <tr>
        <th scope="col" class="py-3 pr-4 font-bold">Project</th>
        <th scope="col" class="px-4 py-3 font-bold">Status</th>
        <th scope="col" class="px-4 py-3 font-bold">Priority</th>
        <th scope="col" class="px-4 py-3 font-bold">Start date</th>
        <th scope="col" class="px-4 py-3 font-bold">Due date</th>
        <th scope="col" class="py-3 pl-4 text-right font-bold">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-rule">
      <tr v-for="project in projects" :key="project.id" class="align-top">
        <td class="py-4 pr-4">
          <p class="font-semibold">{{ project.name }}</p>
          <p class="text-sm text-muted">{{ project.client_name }}</p>
        </td>
        <td class="px-4 py-4"><StatusBadge :status="project.status" /></td>
        <td class="px-4 py-4"><PriorityIndicator :priority="project.priority" /></td>
        <td class="px-4 py-4 text-sm whitespace-nowrap tabular-nums">
          {{ formatDate(project.start_date) }}
        </td>
        <td class="px-4 py-4 text-sm whitespace-nowrap"><DueDate :project="project" /></td>
        <td class="py-3 pl-4">
          <div class="flex justify-end gap-1">
            <button
              type="button"
              :class="actionClass"
              :aria-label="`Edit ${project.name}`"
              @click="emit('edit', project)"
            >
              Edit
            </button>
            <button
              type="button"
              :class="[actionClass, 'text-alert']"
              :aria-label="`Delete ${project.name}`"
              @click="emit('delete', project)"
            >
              Delete
            </button>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

  <!-- Narrow screens: one card per project -->
  <ul class="flex flex-col gap-3 md:hidden">
    <li
      v-for="project in projects"
      :key="project.id"
      class="rounded-md border border-rule bg-white p-4"
    >
      <p class="font-semibold">{{ project.name }}</p>
      <p class="text-sm text-muted">{{ project.client_name }}</p>
      <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
        <StatusBadge :status="project.status" />
        <PriorityIndicator :priority="project.priority" />
      </div>
      <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
        <div>
          <dt class="text-muted">Start date</dt>
          <dd class="tabular-nums">{{ formatDate(project.start_date) }}</dd>
        </div>
        <div>
          <dt class="text-muted">Due date</dt>
          <dd><DueDate :project="project" /></dd>
        </div>
      </dl>
      <div class="mt-3 flex gap-2 border-t border-rule pt-3">
        <button
          type="button"
          :class="actionClass"
          :aria-label="`Edit ${project.name}`"
          @click="emit('edit', project)"
        >
          Edit
        </button>
        <button
          type="button"
          :class="[actionClass, 'text-alert']"
          :aria-label="`Delete ${project.name}`"
          @click="emit('delete', project)"
        >
          Delete
        </button>
      </div>
    </li>
  </ul>
</template>
