import { describe, expect, it } from 'vitest'
import { daysBetween, formatDate, parseDate } from '@/utils/dates'

describe('formatDate', () => {
  it('formats an API date as day, short month and year', () => {
    expect(formatDate('2026-09-30')).toBe('30 Sep 2026')
  })

  it('shows a dash when there is no date', () => {
    expect(formatDate(null)).toBe('—')
  })
})

describe('parseDate', () => {
  it('reads the date as a local calendar day, not UTC midnight', () => {
    const date = parseDate('2026-01-01')

    expect([date.getFullYear(), date.getMonth(), date.getDate()]).toEqual([2026, 0, 1])
  })
})

describe('daysBetween', () => {
  it('counts whole calendar days regardless of the time of day', () => {
    expect(daysBetween(new Date(2026, 8, 30, 23, 59), new Date(2026, 9, 3, 0, 1))).toBe(3)
  })
})
