import { describe, expect, it } from 'vitest'
import {
  DEFAULT_PER_PAGE,
  DEFAULT_SORT,
  filtersFromQuery,
  filtersToQuery,
} from '@/utils/projectFilters'

describe('filtersFromQuery', () => {
  it('reads valid filters from the URL', () => {
    expect(
      filtersFromQuery({
        search: ' acme ',
        status: 'on_hold',
        priority: 'high',
        sort: '-due_date',
        page: '3',
        per_page: '25',
      }),
    ).toEqual({
      search: 'acme',
      status: 'on_hold',
      priority: 'high',
      sort: '-due_date',
      page: 3,
      per_page: 25,
    })
  })

  it('ignores values the API would reject', () => {
    expect(filtersFromQuery({ status: 'archived', priority: 'urgent', sort: 'id' })).toEqual({
      search: undefined,
      status: undefined,
      priority: undefined,
      sort: DEFAULT_SORT,
      page: 1,
      per_page: DEFAULT_PER_PAGE,
    })
  })

  it.each(['0', '-2', '1.5', 'two', '99999999999999999999'])(
    'starts on the first page when the page is %s',
    (page) => {
      expect(filtersFromQuery({ page }).page).toBe(1)
    },
  )

  it('only accepts the page sizes the list offers', () => {
    expect(filtersFromQuery({ per_page: '15' }).per_page).toBe(15)
    expect(filtersFromQuery({ per_page: '1000' }).per_page).toBe(DEFAULT_PER_PAGE)
  })

  it('uses the first value when a parameter is repeated', () => {
    expect(filtersFromQuery({ status: ['planning', 'completed'] }).status).toBe('planning')
  })
})

describe('filtersToQuery', () => {
  it('leaves out empty values and defaults', () => {
    expect(
      filtersToQuery({
        search: '  ',
        status: 'planning',
        sort: DEFAULT_SORT,
        page: 1,
        per_page: DEFAULT_PER_PAGE,
      }),
    ).toEqual({ status: 'planning' })
  })

  it('keeps a later page and a non-default page size', () => {
    expect(filtersToQuery({ page: 2, per_page: 50 })).toEqual({ page: 2, per_page: 50 })
  })

  it('keeps a non-default sort', () => {
    expect(filtersToQuery({ sort: '-priority' })).toEqual({ sort: '-priority' })
  })
})
