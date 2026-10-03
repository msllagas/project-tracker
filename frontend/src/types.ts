export const PROJECT_STATUSES = ['planning', 'in_progress', 'on_hold', 'completed'] as const
export type ProjectStatus = (typeof PROJECT_STATUSES)[number]

export const PROJECT_PRIORITIES = ['low', 'medium', 'high'] as const
export type ProjectPriority = (typeof PROJECT_PRIORITIES)[number]

export const STATUS_LABELS: Record<ProjectStatus, string> = {
  planning: 'Planning',
  in_progress: 'In Progress',
  on_hold: 'On Hold',
  completed: 'Completed',
}

export const PRIORITY_LABELS: Record<ProjectPriority, string> = {
  low: 'Low',
  medium: 'Medium',
  high: 'High',
}

export interface Project {
  id: number
  client_name: string
  name: string
  description: string | null
  status: ProjectStatus
  priority: ProjectPriority
  /** Date in YYYY-MM-DD format. */
  start_date: string | null
  /** Date in YYYY-MM-DD format. */
  due_date: string | null
  created_at: string
  updated_at: string
}

export type ProjectPayload = Pick<
  Project,
  'client_name' | 'name' | 'description' | 'status' | 'priority' | 'start_date' | 'due_date'
>

export const SORTABLE_COLUMNS = [
  'client_name',
  'name',
  'status',
  'priority',
  'start_date',
  'due_date',
  'created_at',
] as const
export type SortableColumn = (typeof SORTABLE_COLUMNS)[number]

/** A sortable column; a leading "-" sorts in descending order. */
export type ProjectSort = SortableColumn | `-${SortableColumn}`

export interface ProjectFilters {
  search?: string
  status?: ProjectStatus
  priority?: ProjectPriority
  sort?: ProjectSort
  page?: number
  per_page?: number
}

/** The `meta` block of a paginated API response. */
export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  /** Position of the first project on the page; null when the page is empty. */
  from: number | null
  to: number | null
}

export interface ProjectPage {
  data: Project[]
  meta: PaginationMeta
}

export interface User {
  id: number
  name: string
  email: string
}

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
}

/** Laravel validation errors, keyed by field name. */
export type ValidationErrors = Record<string, string[]>
