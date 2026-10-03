import type { LocationQuery, LocationQueryRaw } from 'vue-router'
import {
  PROJECT_PRIORITIES,
  PROJECT_STATUSES,
  type ProjectFilters,
  type ProjectPriority,
  type ProjectSort,
  type ProjectStatus,
} from '@/types'

export const DEFAULT_SORT: ProjectSort = '-created_at'

export const SORT_OPTIONS: { value: ProjectSort; label: string }[] = [
  { value: '-created_at', label: 'Newest first' },
  { value: 'due_date', label: 'Due date, soonest first' },
  { value: '-due_date', label: 'Due date, latest first' },
  { value: '-priority', label: 'Priority, highest first' },
  { value: 'priority', label: 'Priority, lowest first' },
  { value: 'status', label: 'Status, planning to completed' },
  { value: 'client_name', label: 'Client name, A to Z' },
  { value: 'name', label: 'Project name, A to Z' },
]

function firstString(value: LocationQuery[string]): string | undefined {
  const first = Array.isArray(value) ? value[0] : value

  return typeof first === 'string' && first.trim() !== '' ? first.trim() : undefined
}

function oneOf<T extends string>(allowed: readonly T[], value: string | undefined): T | undefined {
  return allowed.find((option) => option === value)
}

/** Read filters from the URL, ignoring anything the API would reject. */
export function filtersFromQuery(
  query: LocationQuery,
): Required<Pick<ProjectFilters, 'sort'>> & ProjectFilters {
  return {
    search: firstString(query.search),
    status: oneOf<ProjectStatus>(PROJECT_STATUSES, firstString(query.status)),
    priority: oneOf<ProjectPriority>(PROJECT_PRIORITIES, firstString(query.priority)),
    sort:
      oneOf<ProjectSort>(
        SORT_OPTIONS.map((option) => option.value),
        firstString(query.sort),
      ) ?? DEFAULT_SORT,
  }
}

/** Write filters to the URL, leaving out empty values and the default sort. */
export function filtersToQuery(filters: ProjectFilters): LocationQueryRaw {
  const query: LocationQueryRaw = {}

  if (filters.search?.trim()) query.search = filters.search.trim()
  if (filters.status) query.status = filters.status
  if (filters.priority) query.priority = filters.priority
  if (filters.sort && filters.sort !== DEFAULT_SORT) query.sort = filters.sort

  return query
}
