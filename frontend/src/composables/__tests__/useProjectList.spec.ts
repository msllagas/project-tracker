import { flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { ApiError } from '@/api/client'
import { listProjects } from '@/api/projects'
import { useProjectList } from '@/composables/useProjectList'
import { makePage, makeProject } from '@/test/factories'
import type { ProjectFilters, ProjectPage } from '@/types'

vi.mock('@/api/projects', () => ({ listProjects: vi.fn() }))

const listProjectsMock = vi.mocked(listProjects)

function deferred() {
  let resolve!: (page: ProjectPage) => void
  const promise = new Promise<ProjectPage>((settle) => (resolve = settle))

  return { promise, resolve }
}

beforeEach(() => {
  listProjectsMock.mockReset()
})

describe('useProjectList', () => {
  it('loads the page of projects for the current filters', async () => {
    const page = makePage([makeProject()], { current_page: 2, last_page: 2, total: 11 })
    listProjectsMock.mockResolvedValue(page)

    const list = useProjectList(ref<ProjectFilters>({ status: 'planning', page: 2 }))
    await flushPromises()

    expect(listProjectsMock).toHaveBeenCalledWith({ status: 'planning', page: 2 })
    expect(list.projects.value).toEqual(page.data)
    expect(list.pagination.value).toEqual(page.meta)
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
    fast.resolve(makePage(latest))
    await flushPromises()
    slow.resolve(makePage([makeProject({ name: 'Stale' })]))
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
