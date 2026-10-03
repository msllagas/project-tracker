<script setup lang="ts">
import { computed, useId } from 'vue'
import AppButton from '@/components/AppButton.vue'
import type { PaginationMeta, ProjectFilters } from '@/types'
import { pageNumbers } from '@/utils/pagination'
import { PER_PAGE_OPTIONS } from '@/utils/projectFilters'

const perPageId = useId()

const props = defineProps<{
  pagination: PaginationMeta
}>()

const emit = defineEmits<{
  update: [changes: Pick<ProjectFilters, 'page' | 'per_page'>]
}>()

const pages = computed(() => pageNumbers(props.pagination.current_page, props.pagination.last_page))

function goTo(page: number): void {
  emit('update', { page })
}

function onPerPageChange(event: Event): void {
  emit('update', { per_page: Number((event.target as HTMLSelectElement).value) })
}
</script>

<template>
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center justify-between gap-6 text-sm sm:justify-start">
      <p class="text-muted">
        <template v-if="pagination.total > 0">
          Showing {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }}
        </template>
        <template v-else>Showing 0 of 0</template>
      </p>
      <div class="flex items-center gap-2">
        <label :for="perPageId" class="text-muted">Per page</label>
        <select
          :id="perPageId"
          class="field-control w-20"
          :value="pagination.per_page"
          @change="onPerPageChange"
        >
          <option v-for="option in PER_PAGE_OPTIONS" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
      </div>
    </div>

    <nav aria-label="Pagination" class="flex items-center justify-between gap-2 sm:justify-end">
      <AppButton
        variant="secondary"
        :disabled="pagination.current_page <= 1"
        @click="goTo(pagination.current_page - 1)"
      >
        Previous
      </AppButton>

      <p class="text-sm text-muted sm:hidden">
        Page {{ pagination.current_page }} of {{ pagination.last_page }}
      </p>

      <ol class="hidden items-center gap-1 sm:flex">
        <li v-for="(page, index) in pages" :key="page ?? `gap-${index}`">
          <span v-if="page === null" class="px-1 text-muted" aria-hidden="true">…</span>
          <button
            v-else
            type="button"
            class="h-10.5 min-w-10.5 rounded-md px-2 text-sm font-semibold transition-colors"
            :class="
              page === pagination.current_page ? 'bg-ink text-white' : 'text-ink hover:bg-paper'
            "
            :aria-current="page === pagination.current_page ? 'page' : undefined"
            :aria-label="`Page ${page}`"
            @click="goTo(page)"
          >
            {{ page }}
          </button>
        </li>
      </ol>

      <AppButton
        variant="secondary"
        :disabled="pagination.current_page >= pagination.last_page"
        @click="goTo(pagination.current_page + 1)"
      >
        Next
      </AppButton>
    </nav>
  </div>
</template>
