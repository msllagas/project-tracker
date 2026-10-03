import { request } from '@/api/client'
import type { Project, ProjectFilters, ProjectPayload } from '@/types'

interface ProjectResponse {
  data: Project
}

interface ProjectListResponse {
  data: Project[]
}

export async function listProjects(filters: ProjectFilters = {}): Promise<Project[]> {
  const query = new URLSearchParams()

  for (const [key, value] of Object.entries(filters)) {
    if (value) {
      query.set(key, value)
    }
  }

  const queryString = query.toString()
  const path = queryString ? `/projects?${queryString}` : '/projects'

  return (await request<ProjectListResponse>('GET', path)).data
}

export async function getProject(id: number): Promise<Project> {
  return (await request<ProjectResponse>('GET', `/projects/${id}`)).data
}

export async function createProject(payload: ProjectPayload): Promise<Project> {
  return (await request<ProjectResponse>('POST', '/projects', payload)).data
}

export async function updateProject(id: number, payload: ProjectPayload): Promise<Project> {
  return (await request<ProjectResponse>('PUT', `/projects/${id}`, payload)).data
}

export async function deleteProject(id: number): Promise<void> {
  await request<void>('DELETE', `/projects/${id}`)
}
