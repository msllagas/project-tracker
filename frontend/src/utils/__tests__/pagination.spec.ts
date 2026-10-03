import { describe, expect, it } from 'vitest'
import { pageNumbers } from '@/utils/pagination'

describe('pageNumbers', () => {
  it('shows every page when there are only a few', () => {
    expect(pageNumbers(1, 1)).toEqual([1])
    expect(pageNumbers(2, 5)).toEqual([1, 2, 3, 4, 5])
  })

  it('replaces skipped pages with a gap', () => {
    expect(pageNumbers(1, 10)).toEqual([1, 2, null, 10])
    expect(pageNumbers(6, 10)).toEqual([1, null, 5, 6, 7, null, 10])
    expect(pageNumbers(10, 10)).toEqual([1, null, 9, 10])
  })

  it('shows a single skipped page instead of a gap', () => {
    expect(pageNumbers(4, 10)).toEqual([1, 2, 3, 4, 5, null, 10])
  })
})
