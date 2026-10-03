<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ApiError } from '@/api/client'
import AppButton from '@/components/AppButton.vue'
import AuthLayout from '@/components/AuthLayout.vue'
import FormField from '@/components/FormField.vue'
import { useAuth } from '@/composables/useAuth'

const { register } = useAuth()
const router = useRouter()

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref<ApiError | null>(null)
const submitting = ref(false)

async function submit(): Promise<void> {
  submitting.value = true
  error.value = null

  try {
    await register(form)
    await router.replace({ name: 'projects' })
  } catch (caught) {
    error.value = caught instanceof ApiError ? caught : new ApiError(0, 'Something went wrong.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout title="Create an account" description="Join your team's workspace in a minute.">
    <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
      <p
        v-if="error && !error.isValidationError"
        role="alert"
        class="rounded-md bg-alert/10 px-3 py-2 text-sm text-alert"
      >
        {{ error.message }}
      </p>

      <FormField v-slot="{ control }" label="Name" :error="error?.fieldError('name')">
        <input
          v-bind="control"
          v-model="form.name"
          type="text"
          autocomplete="name"
          required
          class="field-control"
        />
      </FormField>

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

      <FormField
        v-slot="{ control }"
        label="Password"
        hint="At least 8 characters."
        :error="error?.fieldError('password')"
      >
        <input
          v-bind="control"
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          required
          class="field-control"
        />
      </FormField>

      <FormField v-slot="{ control }" label="Confirm password">
        <input
          v-bind="control"
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          required
          class="field-control"
        />
      </FormField>

      <AppButton type="submit" :loading="submitting">
        {{ submitting ? 'Creating account…' : 'Create account' }}
      </AppButton>
    </form>

    <template #footer>
      <p>
        Already have an account?
        <RouterLink
          :to="{ name: 'login' }"
          class="font-semibold text-ink underline underline-offset-2"
        >
          Log in
        </RouterLink>
      </p>
    </template>
  </AuthLayout>
</template>
