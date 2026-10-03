// Fixed names keep dates identical in every browser and in tests (no locale quirks like "Sept").
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

const MS_PER_DAY = 24 * 60 * 60 * 1000

/** Parse a YYYY-MM-DD string as a local calendar date (not UTC midnight). */
export function parseDate(value: string): Date {
  const [year, month, day] = value.split('-').map(Number)

  return new Date(year!, month! - 1, day)
}

/** Format a YYYY-MM-DD string for display, e.g. "15 Jul 2026". */
export function formatDate(value: string | null): string {
  if (!value) {
    return '—'
  }

  const date = parseDate(value)

  return `${date.getDate()} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`
}

function startOfDay(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate())
}

/** Whole calendar days from `later` back to `earlier` (positive when `later` is after). */
export function daysBetween(earlier: Date, later: Date): number {
  return Math.round((startOfDay(later).getTime() - startOfDay(earlier).getTime()) / MS_PER_DAY)
}
