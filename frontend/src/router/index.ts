import { createRouter, createWebHistory } from 'vue-router'
import { onUnauthorized } from '@/api/client'
import { useAuth } from '@/composables/useAuth'

declare module 'vue-router' {
  interface RouteMeta {
    /** Only signed-in users may visit; others are sent to the login page. */
    requiresAuth?: boolean
    /** Only signed-out users may visit; others are sent to the project list. */
    guestOnly?: boolean
  }
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'projects',
      component: () => import('@/views/ProjectsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guestOnly: true },
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const { isAuthenticated, loadUser } = useAuth()

  await loadUser()

  if (to.meta.requiresAuth && !isAuthenticated.value) {
    return { name: 'login', query: to.fullPath === '/' ? {} : { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && isAuthenticated.value) {
    return { name: 'projects' }
  }
})

// When the session expires mid-use, send the user back to the login page.
onUnauthorized(() => {
  useAuth().clearUser()

  const current = router.currentRoute.value

  if (current.meta.requiresAuth) {
    void router.replace({ name: 'login', query: { redirect: current.fullPath } })
  }
})

export default router
