import { beforeEach, describe, expect, it, vi } from 'vitest'

const fetchMock = vi.fn<typeof fetch>()

/** A fresh router (and auth state) for every test. */
async function loadRouter(currentUser: { status: number; body: unknown }) {
  vi.resetModules()
  fetchMock.mockResolvedValue(
    new Response(JSON.stringify(currentUser.body), {
      status: currentUser.status,
      headers: { 'Content-Type': 'application/json' },
    }),
  )

  return (await import('@/router')).default
}

beforeEach(() => {
  vi.stubGlobal('fetch', fetchMock)
  fetchMock.mockReset()
})

describe('route guards', () => {
  it('sends visitors who are not logged in to the login page', async () => {
    const router = await loadRouter({ status: 401, body: { message: 'Unauthenticated.' } })

    await router.push('/')

    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query).toEqual({})
  })

  it('remembers the requested page so login can return to it', async () => {
    const router = await loadRouter({ status: 401, body: { message: 'Unauthenticated.' } })

    await router.push('/?status=on_hold')

    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/?status=on_hold')
  })

  it('sends logged-in users from the login page to the project list', async () => {
    const router = await loadRouter({
      status: 200,
      body: { data: { id: 1, name: 'Demo User', email: 'demo@example.com' } },
    })

    await router.push('/login')

    expect(router.currentRoute.value.name).toBe('projects')
  })
})
