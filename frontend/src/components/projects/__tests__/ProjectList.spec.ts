import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import ProjectList from '@/components/projects/ProjectList.vue'
import { makeProject } from '@/test/factories'

beforeEach(() => {
  vi.useFakeTimers({ toFake: ['Date'] })
  vi.setSystemTime(new Date(2026, 9, 3, 9, 0))
})

afterEach(() => {
  vi.useRealTimers()
})

/** The table row for a project (the list also renders mobile cards). */
function rowText(wrapper: ReturnType<typeof mount>, index = 0): string {
  return wrapper.findAll('tbody tr')[index]!.text()
}

describe('ProjectList', () => {
  it('shows the project, client, status and priority', () => {
    const project = makeProject({
      name: 'Corporate Website Redesign',
      client_name: 'Acme Corporation',
      status: 'on_hold',
      priority: 'high',
    })

    const wrapper = mount(ProjectList, { props: { projects: [project] } })

    expect(rowText(wrapper)).toContain('Corporate Website Redesign')
    expect(rowText(wrapper)).toContain('Acme Corporation')
    expect(rowText(wrapper)).toContain('On Hold')
    expect(rowText(wrapper)).toContain('High')
  })

  it('shows how many days an unfinished project is overdue', () => {
    const wrapper = mount(ProjectList, {
      props: { projects: [makeProject({ status: 'in_progress', due_date: '2026-09-30' })] },
    })

    expect(rowText(wrapper)).toContain('30 Sep 2026')
    expect(rowText(wrapper)).toContain('3 days overdue')
  })

  it('does not mark a completed project as overdue', () => {
    const wrapper = mount(ProjectList, {
      props: { projects: [makeProject({ status: 'completed', due_date: '2026-09-30' })] },
    })

    expect(rowText(wrapper)).not.toContain('overdue')
  })

  it('emits the project when Edit or Delete is pressed', async () => {
    const project = makeProject({ name: 'Mobile App MVP' })
    const wrapper = mount(ProjectList, { props: { projects: [project] } })

    await wrapper.find('tbody button[aria-label="Edit Mobile App MVP"]').trigger('click')
    await wrapper.find('tbody button[aria-label="Delete Mobile App MVP"]').trigger('click')

    expect(wrapper.emitted('edit')).toEqual([[project]])
    expect(wrapper.emitted('delete')).toEqual([[project]])
  })
})
