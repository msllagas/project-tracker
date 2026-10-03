import { ref, watch, type Ref } from 'vue'
import { ApiError } from '@/api/client'
import { listProjects } from '@/api/projects'
import type { Project, ProjectFilters } from '@/types'

/** Load the project list and reload it whenever the filters change. */
export function useProjectList(filters: Ref<ProjectFilters>) {
  const projects = ref<Project[]>([])
  const loading = ref(false)
  const loaded = ref(false)
  const error = ref<ApiError | null>(null)

  let latestRequest = 0

  async function load(): Promise<void> {
    const request = ++latestRequest
    loading.value = true
    error.value = null

    try {
      const result = await listProjects(filters.value)

      // Ignore responses that arrive after a newer request was made.
      if (request === latestRequest) {
        projects.value = result
        loaded.value = true
      }
    } catch (caught) {
      if (request === latestRequest) {
        error.value =
          caught instanceof ApiError ? caught : new ApiError(0, 'The projects could not be loaded.')
      }
    } finally {
      if (request === latestRequest) {
        loading.value = false
      }
    }
  }

  watch(filters, load, { immediate: true })

  return { projects, loading, loaded, error, reload: load }
}
