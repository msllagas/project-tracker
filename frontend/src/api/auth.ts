import { request } from '@/api/client'
import type { LoginCredentials, RegistrationDetails, User } from '@/types'

interface UserResponse {
  data: User
}

export async function fetchCurrentUser(): Promise<User> {
  return (await request<UserResponse>('GET', '/user')).data
}

export async function login(credentials: LoginCredentials): Promise<User> {
  return (await request<UserResponse>('POST', '/login', credentials)).data
}

export async function register(details: RegistrationDetails): Promise<User> {
  return (await request<UserResponse>('POST', '/register', details)).data
}

export async function logout(): Promise<void> {
  await request<void>('POST', '/logout')
}
