import { describe, expect, it } from 'vitest'
import { DEFAULT_SORT, filtersFromQuery, filtersToQuery } from '@/utils/projectFilters'

describe('filtersFromQuery', () => {
  it('reads valid filters from the URL', () => {
    expect(
      filtersFromQuery({
        search: ' acme ',
        status: 'on_hold',
        priority: 'high',
        sort: '-due_date',
      }),
    ).toEqual({ search: 'acme', status: 'on_hold', priority: 'high', sort: '-due_date' })
  })

  it('ignores values the API would reject', () => {
    expect(filtersFromQuery({ status: 'archived', priority: 'urgent', sort: 'id' })).toEqual({
      search: undefined,
      status: undefined,
      priority: undefined,
      sort: DEFAULT_SORT,
    })
  })

  it('uses the first value when a parameter is repeated', () => {
    expect(filtersFromQuery({ status: ['planning', 'completed'] }).status).toBe('planning')
  })
})

describe('filtersToQuery', () => {
  it('leaves out empty values and the default sort', () => {
    expect(filtersToQuery({ search: '  ', status: 'planning', sort: DEFAULT_SORT })).toEqual({
      status: 'planning',
    })
  })

  it('keeps a non-default sort', () => {
    expect(filtersToQuery({ sort: '-priority' })).toEqual({ sort: '-priority' })
  })
})
