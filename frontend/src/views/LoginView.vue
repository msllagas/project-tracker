<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ApiError } from '@/api/client'
import AppButton from '@/components/AppButton.vue'
import AuthLayout from '@/components/AuthLayout.vue'
import FormField from '@/components/FormField.vue'
import { useAuth } from '@/composables/useAuth'

const DEMO_ACCOUNT = { email: 'demo@example.com', password: 'password' }

const { login } = useAuth()
const route = useRoute()
const router = useRouter()

const form = reactive({ email: '', password: '', remember: false })
const error = ref<ApiError | null>(null)
const submitting = ref(false)

/** Only follow redirects to paths inside this app. */
function redirectTarget(): string {
  const redirect = route.query.redirect

  return typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//')
    ? redirect
    : '/'
}

function useDemoAccount(): void {
  Object.assign(form, DEMO_ACCOUNT)
}

async function submit(): Promise<void> {
  submitting.value = true
  error.value = null

  try {
    await login(form)
    await router.replace(redirectTarget())
  } catch (caught) {
    error.value = caught instanceof ApiError ? caught : new ApiError(0, 'Something went wrong.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout title="Log in" description="Track your clients' projects, priorities and deadlines.">
    <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
      <p
        v-if="error && !error.isValidationError"
        role="alert"
        class="rounded-md bg-alert/10 px-3 py-2 text-sm text-alert"
      >
        {{ error.message }}
      </p>

      <FormField v-slot="{ control }" label="Email" :error="error?.fieldError('email')">
        <input
          v-bind="control"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="field-control"
        />
      </FormField>

      <FormField v-slot="{ control }" label="Password" :error="error?.fieldError('password')">
        <input
          v-bind="control"
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          required
          class="field-control"
        />
      </FormField>

      <label class="flex cursor-pointer items-center gap-2 self-start text-sm">
        <input v-model="form.remember" type="checkbox" class="size-4 cursor-pointer accent-ink" />
        Keep me logged in
      </label>

      <AppButton type="submit" :loading="submitting">
        {{ submitting ? 'Logging in…' : 'Log in' }}
      </AppButton>
    </form>

    <template #footer>
      <p>
        Trying it out?
        <button
          type="button"
          class="font-semibold text-ink underline underline-offset-2"
          @click="useDemoAccount"
        >
          Use the demo account
        </button>
      </p>
    </template>
  </AuthLayout>
</template>
