<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import AppLogo from '@/components/AppLogo.vue'
import { useAuth } from '@/composables/useAuth'

const { user, logout } = useAuth()
const router = useRouter()
const loggingOut = ref(false)

async function handleLogout(): Promise<void> {
  loggingOut.value = true

  try {
    await logout()
  } finally {
    loggingOut.value = false
    await router.push({ name: 'login' })
  }
}
</script>

<template>
  <header class="bg-ink">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
      <RouterLink :to="{ name: 'projects' }" class="-m-1.5 rounded-md p-1.5">
        <AppLogo tone="light" />
      </RouterLink>
      <div class="flex items-center gap-3">
        <span v-if="user" class="hidden text-sm text-white/70 sm:inline">{{ user.name }}</span>
        <AppButton variant="ghost-light" :loading="loggingOut" @click="handleLogout">
          Log out
        </AppButton>
      </div>
    </div>
  </header>
</template>
