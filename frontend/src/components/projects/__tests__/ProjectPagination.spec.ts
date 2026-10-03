import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ProjectPagination from '@/components/projects/ProjectPagination.vue'
import type { PaginationMeta } from '@/types'

function mountPagination(meta: Partial<PaginationMeta> = {}) {
  return mount(ProjectPagination, {
    props: {
      pagination: {
        current_page: 2,
        last_page: 3,
        per_page: 10,
        total: 25,
        from: 11,
        to: 20,
        ...meta,
      },
    },
  })
}

function button(wrapper: ReturnType<typeof mountPagination>, text: string) {
  return wrapper.findAll('button').find((candidate) => candidate.text() === text)!
}

describe('ProjectPagination', () => {
  it('shows which projects are on the page', () => {
    expect(mountPagination().text()).toContain('Showing 11–20 of 25')
  })

  it('marks the current page', () => {
    const wrapper = mountPagination()

    expect(wrapper.find('[aria-current="page"]').text()).toBe('2')
  })

  it('asks for the previous, next or chosen page', async () => {
    const wrapper = mountPagination()

    await button(wrapper, 'Previous').trigger('click')
    await button(wrapper, 'Next').trigger('click')
    await button(wrapper, '3').trigger('click')

    expect(wrapper.emitted('update')).toEqual([[{ page: 1 }], [{ page: 3 }], [{ page: 3 }]])
  })

  it('cannot go before the first or past the last page', () => {
    expect(button(mountPagination({ current_page: 1 }), 'Previous').attributes()).toHaveProperty(
      'disabled',
    )
    expect(button(mountPagination({ current_page: 3 }), 'Next').attributes()).toHaveProperty(
      'disabled',
    )
  })

  it('asks for a different page size', async () => {
    const wrapper = mountPagination()

    await wrapper.find('select').setValue('25')

    expect(wrapper.emitted('update')).toEqual([[{ per_page: 25 }]])
  })

  it('stays visible when everything fits on one page', () => {
    const wrapper = mountPagination({ current_page: 1, last_page: 1, total: 4, from: 1, to: 4 })

    expect(wrapper.text()).toContain('Showing 1–4 of 4')
    expect(wrapper.find('[aria-current="page"]').text()).toBe('1')
    expect(button(wrapper, 'Previous').attributes()).toHaveProperty('disabled')
    expect(button(wrapper, 'Next').attributes()).toHaveProperty('disabled')
  })

  it('stays visible when there are no projects', () => {
    const wrapper = mountPagination({
      current_page: 1,
      last_page: 1,
      total: 0,
      from: null,
      to: null,
    })

    expect(wrapper.text()).toContain('Showing 0 of 0')
    expect(wrapper.find('nav').exists()).toBe(true)
    expect(wrapper.find('select').exists()).toBe(true)
  })
})
