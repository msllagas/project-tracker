import { describe, expect, it } from 'vitest'
import { makeProject } from '@/test/factories'
import { daysOverdue } from '@/utils/projects'

const today = new Date(2026, 9, 3)

describe('daysOverdue', () => {
  it('counts the days an unfinished project is past its due date', () => {
    expect(daysOverdue(makeProject({ due_date: '2026-09-30' }), today)).toBe(3)
  })

  it('returns 0 for a project due today or later', () => {
    expect(daysOverdue(makeProject({ due_date: '2026-10-03' }), today)).toBe(0)
  })

  it('never treats a completed project as overdue', () => {
    expect(daysOverdue(makeProject({ status: 'completed', due_date: '2026-01-01' }), today)).toBe(0)
  })

  it('returns 0 when the project has no due date', () => {
    expect(daysOverdue(makeProject({ due_date: null }), today)).toBe(0)
  })
})
