import { flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { ApiError } from '@/api/client'
import { listProjects } from '@/api/projects'
import { useProjectList } from '@/composables/useProjectList'
import { makeProject } from '@/test/factories'
import type { Project, ProjectFilters } from '@/types'

vi.mock('@/api/projects', () => ({ listProjects: vi.fn() }))

const listProjectsMock = vi.mocked(listProjects)

function deferred() {
  let resolve!: (projects: Project[]) => void
  const promise = new Promise<Project[]>((settle) => (resolve = settle))

  return { promise, resolve }
}

beforeEach(() => {
  listProjectsMock.mockReset()
})

describe('useProjectList', () => {
  it('loads the projects for the current filters', async () => {
    const projects = [makeProject()]
    listProjectsMock.mockResolvedValue(projects)

    const list = useProjectList(ref<ProjectFilters>({ status: 'planning' }))
    await flushPromises()

    expect(listProjectsMock).toHaveBeenCalledWith({ status: 'planning' })
    expect(list.projects.value).toEqual(projects)
    expect(list.loaded.value).toBe(true)
  })

  it('ignores a slow response that arrives after a newer one', async () => {
    const slow = deferred()
    const fast = deferred()
    const latest = [makeProject({ name: 'Latest' })]
    listProjectsMock.mockReturnValueOnce(slow.promise).mockReturnValueOnce(fast.promise)
    const filters = ref<ProjectFilters>({ search: 'a' })

    const list = useProjectList(filters)
    filters.value = { search: 'ac' }
    await flushPromises()
    fast.resolve(latest)
    await flushPromises()
    slow.resolve([makeProject({ name: 'Stale' })])
    await flushPromises()

    expect(list.projects.value).toEqual(latest)
    expect(list.loading.value).toBe(false)
  })

  it('exposes the error when loading fails', async () => {
    listProjectsMock.mockRejectedValue(new ApiError(500, 'Server Error'))

    const list = useProjectList(ref<ProjectFilters>({}))
    await flushPromises()

    expect(list.error.value?.message).toBe('Server Error')
    expect(list.loaded.value).toBe(false)
  })
})
