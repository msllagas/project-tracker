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

  /** Apply changes; anything other than a page change starts again from the first page. */
  function updateFilters(changes: Partial<ProjectFilters>): void {
    void router.replace({ query: filtersToQuery({ ...filters.value, page: 1, ...changes }) })
  }

  /** Clear search, status and priority but keep the sort order and page size. */
  function clearFilters(): void {
    const { sort, per_page } = filters.value

    void router.replace({ query: filtersToQuery({ sort, per_page }) })
  }

  return { filters, hasActiveFilters, updateFilters, clearFilters }
}
