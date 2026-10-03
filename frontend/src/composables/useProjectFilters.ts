import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { ProjectFilters } from '@/types'
import { filtersFromQuery, filtersToQuery } from '@/utils/projectFilters'

/** Project list filters, stored in the URL so views can be refreshed and shared. */
export function useProjectFilters() {
  const route = useRoute()
  const router = useRouter()

  const filters = computed(() => filtersFromQuery(route.query))

  const hasActiveFilters = computed(() =>
    Boolean(filters.value.search || filters.value.status || filters.value.priority),
  )

  function updateFilters(changes: Partial<ProjectFilters>): void {
    void router.replace({ query: filtersToQuery({ ...filters.value, ...changes }) })
  }

  /** Clear search, status and priority but keep the chosen sort order. */
  function clearFilters(): void {
    void router.replace({ query: filtersToQuery({ sort: filters.value.sort }) })
  }

  return { filters, hasActiveFilters, updateFilters, clearFilters }
}
