import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError, onUnauthorized, request } from '@/api/client'

const fetchMock = vi.fn<typeof fetch>()

function json(status: number, body: unknown): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  })
}

function setXsrfCookie(value: string): void {
  document.cookie = `XSRF-TOKEN=${encodeURIComponent(value)}; path=/`
}

function headersOf(call: number): Record<string, string> {
  return fetchMock.mock.calls[call]![1]!.headers as Record<string, string>
}

beforeEach(() => {
  vi.stubGlobal('fetch', fetchMock)
  fetchMock.mockReset()
})

afterEach(() => {
  document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/'
  onUnauthorized(() => {})
})

describe('request', () => {
  it('sends JSON with the decoded XSRF token on state-changing requests', async () => {
    setXsrfCookie('token=value')
    fetchMock.mockResolvedValue(json(201, { data: { id: 1 } }))

    const result = await request('POST', '/projects', { name: 'Site' })

    expect(result).toEqual({ data: { id: 1 } })
    expect(fetchMock).toHaveBeenCalledWith(
      '/api/projects',
      expect.objectContaining({ method: 'POST', body: '{"name":"Site"}' }),
    )
    expect(headersOf(0)).toMatchObject({
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-XSRF-TOKEN': 'token=value',
    })
  })

  it('does not send a CSRF token on GET requests', async () => {
    setXsrfCookie('token')
    fetchMock.mockResolvedValue(json(200, { data: [] }))

    await request('GET', '/projects')

    expect(headersOf(0)).not.toHaveProperty('X-XSRF-TOKEN')
  })

  it('fetches the CSRF cookie first when it is missing', async () => {
    fetchMock.mockImplementation(async (input) => {
      if (input === '/sanctum/csrf-cookie') {
        setXsrfCookie('fresh')
        return new Response(null, { status: 204 })
      }

      return json(200, { data: { id: 1 } })
    })

    await request('POST', '/login', {})

    expect(fetchMock.mock.calls.map(([input]) => input)).toEqual([
      '/sanctum/csrf-cookie',
      '/api/login',
    ])
    expect(headersOf(1)['X-XSRF-TOKEN']).toBe('fresh')
  })

  it('refreshes the CSRF cookie and retries once after a 419', async () => {
    setXsrfCookie('expired')
    fetchMock
      .mockResolvedValueOnce(json(419, { message: 'CSRF token mismatch.' }))
      .mockImplementationOnce(async () => {
        setXsrfCookie('renewed')
        return new Response(null, { status: 204 })
      })
      .mockResolvedValueOnce(new Response(null, { status: 204 }))

    await request('DELETE', '/projects/1')

    expect(fetchMock).toHaveBeenCalledTimes(3)
    expect(headersOf(2)['X-XSRF-TOKEN']).toBe('renewed')
  })

  it('returns undefined for a 204 response', async () => {
    setXsrfCookie('token')
    fetchMock.mockResolvedValue(new Response(null, { status: 204 }))

    await expect(request('DELETE', '/projects/1')).resolves.toBeUndefined()
  })

  it('throws an ApiError with field messages for a 422 response', async () => {
    setXsrfCookie('token')
    fetchMock.mockResolvedValue(
      json(422, {
        message: 'The due date cannot be earlier than the start date.',
        errors: { due_date: ['The due date cannot be earlier than the start date.'] },
      }),
    )

    const error = await request('POST', '/projects', {}).catch((caught: unknown) => caught)

    expect(error).toBeInstanceOf(ApiError)
    expect((error as ApiError).isValidationError).toBe(true)
    expect((error as ApiError).fieldError('due_date')).toBe(
      'The due date cannot be earlier than the start date.',
    )
  })

  it('calls the unauthorized handler when the session has ended', async () => {
    const handler = vi.fn()
    onUnauthorized(handler)
    fetchMock.mockResolvedValue(json(401, { message: 'Unauthenticated.' }))

    await expect(request('GET', '/projects')).rejects.toMatchObject({ status: 401 })
    expect(handler).toHaveBeenCalledOnce()
  })

  it('reports a network failure as status 0 with a readable message', async () => {
    fetchMock.mockRejectedValue(new TypeError('Failed to fetch'))

    await expect(request('GET', '/projects')).rejects.toMatchObject({
      status: 0,
      message: 'Unable to reach the server. Check your connection and try again.',
    })
  })

  it('explains a rate limit with the wait time', async () => {
    fetchMock.mockResolvedValue(
      new Response(JSON.stringify({ message: 'Too Many Attempts.' }), {
        status: 429,
        headers: { 'Content-Type': 'application/json', 'Retry-After': '42' },
      }),
    )

    await expect(request('GET', '/projects')).rejects.toMatchObject({
      status: 429,
      message: 'Too many requests. Try again in 42 seconds.',
    })
  })

  it('hides server error details from the user', async () => {
    fetchMock.mockResolvedValue(
      json(500, { message: 'SQLSTATE[22P02]: Invalid text representation' }),
    )

    await expect(request('GET', '/projects')).rejects.toMatchObject({
      status: 500,
      message: 'Something went wrong on the server. Try again in a moment.',
    })
  })
})
