import type { ValidationErrors } from '@/types'

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE'

/** An error response from the API, or a network failure (status 0). */
export class ApiError extends Error {
  readonly status: number
  readonly errors: ValidationErrors

  constructor(status: number, message: string, errors: ValidationErrors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }

  get isValidationError(): boolean {
    return this.status === 422
  }

  /** The first validation message for a field, if any. */
  fieldError(field: string): string | undefined {
    return this.errors[field]?.[0]
  }
}

let unauthorizedHandler: (() => void) | undefined

/** Register what should happen when the API reports that the session has ended. */
export function onUnauthorized(handler: () => void): void {
  unauthorizedHandler = handler
}

function readCookie(name: string): string | undefined {
  const cookie = document.cookie.split('; ').find((entry) => entry.startsWith(`${name}=`))

  return cookie ? decodeURIComponent(cookie.slice(name.length + 1)) : undefined
}

/** Ask Laravel for a fresh XSRF-TOKEN cookie. */
async function refreshCsrfCookie(): Promise<void> {
  await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' })
}

async function send(method: HttpMethod, path: string, body?: unknown): Promise<Response> {
  const headers: Record<string, string> = { Accept: 'application/json' }

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }

  if (method !== 'GET') {
    if (!readCookie('XSRF-TOKEN')) {
      await refreshCsrfCookie()
    }

    headers['X-XSRF-TOKEN'] = readCookie('XSRF-TOKEN') ?? ''
  }

  return fetch(`/api${path}`, {
    method,
    headers,
    body: body === undefined ? undefined : JSON.stringify(body),
    credentials: 'same-origin',
  })
}

async function toApiError(response: Response): Promise<ApiError> {
  const payload = (await response.json().catch(() => ({}))) as {
    message?: string
    errors?: ValidationErrors
  }

  return new ApiError(
    response.status,
    payload.message || response.statusText || 'Something went wrong.',
    payload.errors,
  )
}

/**
 * Call the Laravel API and return the parsed JSON body.
 *
 * Sends the CSRF token for state-changing requests, retries once when the token
 * has expired (419), and throws an ApiError for every unsuccessful response.
 */
export async function request<T>(method: HttpMethod, path: string, body?: unknown): Promise<T> {
  let response: Response

  try {
    response = await send(method, path, body)

    if (response.status === 419) {
      await refreshCsrfCookie()
      response = await send(method, path, body)
    }
  } catch {
    throw new ApiError(0, 'Unable to reach the server. Check your connection and try again.')
  }

  if (!response.ok) {
    const error = await toApiError(response)

    if (error.status === 401) {
      unauthorizedHandler?.()
    }

    throw error
  }

  return response.status === 204 ? (undefined as T) : ((await response.json()) as T)
}
