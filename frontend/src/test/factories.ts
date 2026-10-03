import type { Project } from '@/types'

let nextId = 1

export function makeProject(overrides: Partial<Project> = {}): Project {
  const id = nextId++

  return {
    id,
    client_name: 'Acme Corporation',
    name: `Project ${id}`,
    description: null,
    status: 'in_progress',
    priority: 'medium',
    start_date: '2026-09-01',
    due_date: '2026-12-01',
    created_at: '2026-09-01T00:00:00.000000Z',
    updated_at: '2026-09-01T00:00:00.000000Z',
    ...overrides,
  }
}
