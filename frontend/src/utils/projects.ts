import { daysBetween, parseDate } from '@/utils/dates'
import type { Project } from '@/types'

/** Days a project is past its due date, or 0 when it is on time, undated or completed. */
export function daysOverdue(project: Project, today: Date = new Date()): number {
  if (!project.due_date || project.status === 'completed') {
    return 0
  }

  return Math.max(0, daysBetween(parseDate(project.due_date), today))
}
