import { flushPromises, mount, type VueWrapper } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/client'
import { createProject, updateProject } from '@/api/projects'
import ProjectFormDialog from '@/components/projects/ProjectFormDialog.vue'
import { makeProject } from '@/test/factories'
import type { Project } from '@/types'

vi.mock('@/api/projects', () => ({ createProject: vi.fn(), updateProject: vi.fn() }))

const createProjectMock = vi.mocked(createProject)
const updateProjectMock = vi.mocked(updateProject)

let wrapper: VueWrapper | undefined

function mountDialog(project: Project | null = null) {
  wrapper = mount(ProjectFormDialog, {
    props: { open: true, project },
    attachTo: document.body,
  })

  return wrapper
}

/** The form control labelled `label`. */
function field(dialog: VueWrapper, label: string) {
  const labelElement = dialog.findAll('label').find((element) => element.text() === label)

  return dialog.find(`#${CSS.escape(labelElement!.attributes('for')!)}`)
}

async function submit(dialog: VueWrapper): Promise<void> {
  await dialog.find('form').trigger('submit')
  await flushPromises()
}

beforeEach(() => {
  createProjectMock.mockReset()
  updateProjectMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
})

describe('ProjectFormDialog', () => {
  it('creates a project and sends blank optional fields as null', async () => {
    const created = makeProject()
    createProjectMock.mockResolvedValue(created)
    const dialog = mountDialog()

    await field(dialog, 'Client name').setValue('  GreenLeaf Cafe ')
    await field(dialog, 'Project name').setValue('Online Ordering System')
    await submit(dialog)

    expect(createProjectMock).toHaveBeenCalledWith({
      client_name: 'GreenLeaf Cafe',
      name: 'Online Ordering System',
      description: null,
      status: 'planning',
      priority: 'medium',
      start_date: null,
      due_date: null,
    })
    expect(dialog.emitted('saved')).toEqual([[created, 'created']])
  })

  it('shows server validation messages under the matching fields', async () => {
    createProjectMock.mockRejectedValue(
      new ApiError(422, 'The given data was invalid.', {
        due_date: ['The due date cannot be earlier than the start date.'],
      }),
    )
    const dialog = mountDialog()

    await submit(dialog)

    const dueDate = field(dialog, 'Due date')
    const message = dialog.find(`#${CSS.escape(dueDate.attributes('aria-describedby')!)}`)
    expect(dueDate.attributes('aria-invalid')).toBe('true')
    expect(message.text()).toBe('The due date cannot be earlier than the start date.')
    expect(dialog.emitted('saved')).toBeUndefined()
  })

  it('shows other errors in an alert above the form', async () => {
    createProjectMock.mockRejectedValue(
      new ApiError(0, 'Unable to reach the server. Check your connection and try again.'),
    )
    const dialog = mountDialog()

    await submit(dialog)

    expect(dialog.find('[role="alert"]').text()).toBe(
      'Unable to reach the server. Check your connection and try again.',
    )
  })

  it('pre-fills the form and saves changes to an existing project', async () => {
    const project = makeProject({
      client_name: 'Nova Fitness',
      name: 'Mobile App MVP',
      priority: 'high',
      start_date: '2026-06-05',
      due_date: '2026-08-20',
    })
    updateProjectMock.mockResolvedValue({ ...project, status: 'completed' })
    const dialog = mountDialog(project)

    expect((field(dialog, 'Client name').element as HTMLInputElement).value).toBe('Nova Fitness')
    await field(dialog, 'Status').setValue('completed')
    await submit(dialog)

    expect(updateProjectMock).toHaveBeenCalledWith(project.id, {
      client_name: 'Nova Fitness',
      name: 'Mobile App MVP',
      description: null,
      status: 'completed',
      priority: 'high',
      start_date: '2026-06-05',
      due_date: '2026-08-20',
    })
    expect(dialog.emitted('saved')?.[0]?.[1]).toBe('updated')
  })
})
