import { computed, readonly, ref } from 'vue'
import * as authApi from '@/api/auth'
import { ApiError } from '@/api/client'
import type { LoginCredentials, User } from '@/types'

const user = ref<User | null>(null)
let userRequest: Promise<void> | null = null

/** Load the signed-in user once; later calls reuse the same request. */
function loadUser(): Promise<void> {
  userRequest ??= authApi
    .fetchCurrentUser()
    .then((currentUser) => {
      user.value = currentUser
    })
    .catch((error: unknown) => {
      user.value = null

      // A 401 just means "not logged in"; anything else is retried on the next navigation.
      if (!(error instanceof ApiError && error.status === 401)) {
        userRequest = null
      }
    })

  return userRequest
}

async function login(credentials: LoginCredentials): Promise<void> {
  user.value = await authApi.login(credentials)
  userRequest = Promise.resolve()
}

async function logout(): Promise<void> {
  try {
    await authApi.logout()
  } finally {
    clearUser()
  }
}

function clearUser(): void {
  user.value = null
  userRequest = Promise.resolve()
}

/** Shared authentication state for the whole app. */
export function useAuth() {
  return {
    user: readonly(user),
    isAuthenticated: computed(() => user.value !== null),
    loadUser,
    login,
    logout,
    clearUser,
  }
}
