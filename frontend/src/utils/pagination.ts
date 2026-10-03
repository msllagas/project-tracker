/**
 * The page numbers to show for a page in a list of pages: the first and last
 * page, and the pages around the current one. `null` marks a gap of skipped
 * pages; a gap of a single page shows that page instead.
 */
export function pageNumbers(current: number, last: number): (number | null)[] {
  const shown = [...new Set([1, current - 1, current, current + 1, last])]
    .filter((page) => page >= 1 && page <= last)
    .sort((a, b) => a - b)

  const pages: (number | null)[] = []

  for (const page of shown) {
    const previous = pages.at(-1)

    if (typeof previous === 'number' && page - previous === 2) {
      pages.push(previous + 1)
    } else if (typeof previous === 'number' && page - previous > 2) {
      pages.push(null)
    }

    pages.push(page)
  }

  return pages
}
