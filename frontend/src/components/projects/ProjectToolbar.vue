<script setup lang="ts">
import { onBeforeUnmount, ref, useId, watch } from 'vue'
import {
  PRIORITY_LABELS,
  PROJECT_PRIORITIES,
  PROJECT_STATUSES,
  STATUS_LABELS,
  type ProjectFilters,
} from '@/types'
import { SORT_OPTIONS } from '@/utils/projectFilters'

const SEARCH_DELAY_MS = 300

const ids = { search: useId(), status: useId(), priority: useId(), sort: useId() }

const props = defineProps<{
  filters: ProjectFilters
  hasActiveFilters: boolean
}>()

const emit = defineEmits<{
  update: [changes: Partial<ProjectFilters>]
  clear: []
}>()

const searchText = ref(props.filters.search ?? '')
let searchTimer: ReturnType<typeof setTimeout> | undefined

// Keep the box in sync when the URL changes elsewhere (back button, "Clear filters").
watch(
  () => props.filters.search,
  (search) => {
    if ((search ?? '') !== searchText.value.trim()) {
      searchText.value = search ?? ''
    }
  },
)

function onSearchInput(): void {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => emit('update', { search: searchText.value }), SEARCH_DELAY_MS)
}

function onSelect(key: 'status' | 'priority' | 'sort', event: Event): void {
  const value = (event.target as HTMLSelectElement).value

  emit('update', { [key]: value || undefined })
}

onBeforeUnmount(() => clearTimeout(searchTimer))
</script>

<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
    <div class="flex grow flex-col gap-1.5">
      <label :for="ids.search" class="text-sm font-medium">Search</label>
      <input
        :id="ids.search"
        v-model="searchText"
        type="search"
        placeholder="Client or project name"
        class="field-control"
        @input="onSearchInput"
      />
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:flex">
      <div class="flex flex-col gap-1.5">
        <label :for="ids.status" class="text-sm font-medium">Status</label>
        <select
          :id="ids.status"
          class="field-control lg:w-40"
          :value="filters.status ?? ''"
          @change="onSelect('status', $event)"
        >
          <option value="">All statuses</option>
          <option v-for="status in PROJECT_STATUSES" :key="status" :value="status">
            {{ STATUS_LABELS[status] }}
          </option>
        </select>
      </div>

      <div class="flex flex-col gap-1.5">
        <label :for="ids.priority" class="text-sm font-medium">Priority</label>
        <select
          :id="ids.priority"
          class="field-control lg:w-36"
          :value="filters.priority ?? ''"
          @change="onSelect('priority', $event)"
        >
          <option value="">All priorities</option>
          <option v-for="priority in PROJECT_PRIORITIES" :key="priority" :value="priority">
            {{ PRIORITY_LABELS[priority] }}
          </option>
        </select>
      </div>

      <div class="col-span-2 flex flex-col gap-1.5 sm:col-span-1">
        <label :for="ids.sort" class="text-sm font-medium">Sort by</label>
        <select
          :id="ids.sort"
          class="field-control lg:w-60"
          :value="filters.sort"
          @change="onSelect('sort', $event)"
        >
          <option v-for="option in SORT_OPTIONS" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>
    </div>

    <button
      v-if="hasActiveFilters"
      type="button"
      class="self-start rounded-md px-1 py-2.5 text-sm font-semibold underline underline-offset-2 lg:self-end"
      @click="emit('clear')"
    >
      Clear filters
    </button>
  </div>
</template>
